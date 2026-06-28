<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Support\SqlDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BoardController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);

        $activeSprint = SqlDialect::normalizeSprint(DB::selectOne(
            "SELECT s.* FROM sprints s
             WHERE s.project_id = ? AND s.status = 'active'
             ORDER BY s.start_date DESC
            ",
            [$project],
        ));

        $workflow = [
            'selected' => 'Selected',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
        ];

        $issuesByStatus = collect();

        if ($activeSprint) {
            $issues = SqlDialect::mapIssues(DB::select(
                'SELECT i.*,
                        assignee.name AS assignee_name,
                        team.name AS team_name
                 FROM issues i
                 LEFT JOIN users assignee ON assignee.id = i.assignee_id
                 LEFT JOIN teams team ON team.id = i.team_id
                 WHERE i.sprint_id = ?
                 ORDER BY i.key',
                [$activeSprint->id],
            ));
            $activeSprint->issues = $issues;
            $issuesByStatus = $issues->groupBy('status');
        }

        $columns = collect($workflow)->map(fn ($label, $status) => [
            'status' => $status,
            'label' => $label,
            'issues' => $issuesByStatus->get($status, collect()),
        ]);

        return view('projects.board.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'activeSprint' => $activeSprint,
            'columns' => $columns,
            'workflow' => $workflow,
        ]);
    }

    public function updateIssueStatus(Request $request, string $project, string $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $issueRow = DB::selectOne(
            'SELECT id, key, project_id, sprint_id, status, reporter_id, assignee_id
             FROM issues WHERE id = ? AND project_id = ?',
            [$issue, $project],
        );
        abort_if($issueRow === null, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['selected', 'in_progress', 'review', 'done'])],
        ]);

        $activeSprint = DB::selectOne(
            "SELECT id FROM sprints WHERE project_id = ? AND status = 'active'",
            [$project],
        );

        abort_unless($activeSprint && $issueRow->sprint_id === $activeSprint->id, 422);

        $oldStatus = $issueRow->status;

        SqlDialect::updateIssueStatus($issue, $validated['status']);

        $this->logActivity(
            $project,
            $request->user()->id,
            'changed issue status',
            'App\Models\Issue',
            $issue,
            $issue,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $validated['status']],
        );

        $userIds = collect([$issueRow->reporter_id, $issueRow->assignee_id])->filter()->unique()->values()->all();
        $url = route('projects.issues.show', [$project, $issue]);

        foreach ($userIds as $userId) {
            $this->pushNotification(
                $userId,
                'Issue status changed',
                "{$issueRow->key} moved from {$oldStatus} to {$validated['status']}.",
                $url,
                $project,
                $issue,
            );
        }

        return redirect()
            ->route('projects.board.index', $project)
            ->with('status', 'Issue status updated.');
    }
}
