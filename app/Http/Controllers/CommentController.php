<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Support\SqlDialect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    use AuthorizesProjectMembership;

    public function store(Request $request, string $project, string $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $issueRow = DB::selectOne(
            'SELECT id, key, reporter_id, assignee_id FROM issues WHERE id = ? AND project_id = ?',
            [$issue, $project],
        );
        abort_if($issueRow === null, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $commentId = (string) Str::uuid();
        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO comments (id, issue_id, user_id, body, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $commentId,
                $issue,
                $request->user()->id,
                $validated['body'],
                $now,
                $now,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'commented on issue',
            'App\Models\Comment',
            $commentId,
            $issue,
            newValues: [
                'issue' => $issueRow->key,
                'comment' => str($validated['body'])->limit(140)->toString(),
            ],
        );

        $url = route('projects.issues.show', [$project, $issue]);
        $userIds = collect([$issueRow->reporter_id, $issueRow->assignee_id])->filter()->unique()->values()->all();

        foreach ($userIds as $userId) {
            $this->pushNotification(
                $userId,
                'New comment',
                "{$request->user()->name} commented on {$issueRow->key}.",
                $url,
                $project,
                $issue,
            );
        }

        return redirect()
            ->route('projects.issues.show', [$project, $issue])
            ->with('status', 'Comment added.');
    }

    public function destroy(Request $request, string $project, string $issue, string $comment): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $issueRow = DB::selectOne('SELECT key FROM issues WHERE id = ? AND project_id = ?', [$issue, $project]);
        abort_if($issueRow === null, 404);

        $commentRow = DB::selectOne('SELECT body FROM comments WHERE id = ? AND issue_id = ?', [$comment, $issue]);
        abort_if($commentRow === null, 404);
        SqlDialect::normalizeComment($commentRow);

        DB::delete('DELETE FROM comments WHERE id = ?', [$comment]);

        $this->logActivity(
            $project,
            $request->user()->id,
            'deleted issue comment',
            'App\Models\Comment',
            $comment,
            $issue,
            oldValues: [
                'issue' => $issueRow->key,
                'comment' => str($commentRow->body)->limit(40)->toString(),
            ],
        );

        return redirect()
            ->route('projects.issues.show', [$project, $issue])
            ->with('status', 'Comment deleted.');
    }
}
