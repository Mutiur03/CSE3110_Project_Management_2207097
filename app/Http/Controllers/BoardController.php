<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Project;
use App\Notifications\ProjectEventNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoardController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        $activeSprint = $project->sprints()
            ->where('status', 'active')
            ->with(['issues.assignee', 'issues.team'])
            ->latest('start_date')
            ->first();

        $workflow = [
            'selected' => 'Selected',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
        ];

        $issuesByStatus = $activeSprint
            ? $activeSprint->issues->groupBy('status')
            : collect();

        $columns = collect($workflow)->map(fn ($label, $status) => [
            'status' => $status,
            'label' => $label,
            'issues' => $issuesByStatus->get($status, collect()),
        ]);

        return view('projects.board.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'activeSprint' => $activeSprint,
            'columns' => $columns,
            'workflow' => $workflow,
        ]);
    }

    public function updateIssueStatus(Request $request, Project $project, Issue $issue): RedirectResponse
    {
        $this->authorizeProjectAccess($request, $project);
        abort_unless($issue->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['selected', 'in_progress', 'review', 'done'])],
        ]);

        $activeSprint = $project->sprints()
            ->where('status', 'active')
            ->first();

        abort_unless($activeSprint && $issue->sprint_id === $activeSprint->id, 422);

        $oldStatus = $issue->status;
        $issue->update(['status' => $validated['status']]);

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'changed issue status',
            'subject_type' => Issue::class,
            'subject_id' => $issue->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $issue->status],
        ]);

        $issue->loadMissing(['reporter', 'assignee']);
        collect([$issue->reporter, $issue->assignee])
            ->filter()
            ->unique('id')
            ->each(fn ($user) => (new ProjectEventNotification(
                'Issue status changed',
                "{$issue->key} moved from {$oldStatus} to {$issue->status}.",
                route('projects.issues.show', [$project, $issue]),
                $project->id,
                $issue->id,
            ))->sendTo($user));

        return redirect()
            ->route('projects.board.index', $project)
            ->with('status', 'Issue status updated.');
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        abort_unless(
            $project->owner_id === $request->user()->id
                || $project->members()->where('users.id', $request->user()->id)->exists(),
            403
        );
    }

    private function userProjects(Request $request)
    {
        return Project::query()
            ->where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->orderBy('name')
            ->get();
    }
}
