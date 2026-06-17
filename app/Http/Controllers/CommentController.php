<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Notifications\ProjectEventNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use AuthorizesProjectMembership;

    public function store(Request $request, Project $project, Issue $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertIssueBelongsToProject($issue, $project);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $comment = Comment::create([
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'commented on issue',
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'new_values' => [
                'issue' => $issue->key,
                'comment' => str($comment->body)->limit(140)->toString(),
            ],
        ]);

        $recipients = collect([$issue->reporter, $issue->assignee])
            ->filter()
            ->unique('id');

        $recipients->each(fn ($user) => (new ProjectEventNotification(
            'New comment',
            "{$request->user()->name} commented on {$issue->key}.",
            route('projects.issues.show', [$project, $issue]),
            $project->id,
            $issue->id,
        ))->sendTo($user));

        return redirect()
            ->route('projects.issues.show', [$project, $issue])
            ->with('status', 'Comment added.');
    }

    public function destroy(Request $request, Project $project, Issue $issue, Comment $comment): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertIssueBelongsToProject($issue, $project);
        abort_unless($comment->issue_id === $issue->id, 404);

        $oldBody = $comment->body;
        $comment->delete();

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'deleted issue comment',
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'old_values' => [
                'issue' => $issue->key,
                'comment' => str($oldBody)->limit(40)->toString(),
            ],
        ]);

        return redirect()
            ->route('projects.issues.show', [$project, $issue])
            ->with('status', 'Comment deleted.');
    }

    private function assertIssueBelongsToProject(Issue $issue, Project $project): void
    {
        abort_unless($issue->project_id === $project->id, 404);
    }
}
