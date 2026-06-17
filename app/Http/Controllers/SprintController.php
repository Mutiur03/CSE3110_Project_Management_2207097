<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Notifications\ProjectEventNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SprintController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        return view('projects.sprints.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'sprints' => $project->sprints()
                ->with(['issues.assignee', 'issues.team'])
                ->withCount('issues')
                ->orderByRaw("case status when 'active' then 0 when 'planned' then 1 else 2 end")
                ->latest()
                ->get(),
            'backlogIssues' => $project->issues()
                ->whereNull('sprint_id')
                ->where('status', 'backlog')
                ->whereIn('type', ['story', 'task', 'subtask', 'bug'])
                ->with(['assignee', 'team'])
                ->orderBy('key')
                ->get(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $sprint = Sprint::create([
            ...$validated,
            'project_id' => $project->id,
            'status' => 'planned',
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'created sprint',
            'subject_type' => Sprint::class,
            'subject_id' => $sprint->id,
            'new_values' => [
                'name' => $sprint->name,
                'status' => $sprint->status,
            ],
        ]);

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint created.');
    }

    public function update(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertSprintBelongsToProject($sprint, $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $oldValues = $sprint->only(['name', 'goal', 'start_date', 'end_date']);
        $sprint->update($validated);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'updated sprint',
            'subject_type' => Sprint::class,
            'subject_id' => $sprint->id,
            'old_values' => $oldValues,
            'new_values' => $sprint->only(['name', 'goal', 'start_date', 'end_date']),
        ]);

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint updated.');
    }

    public function addIssue(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertSprintBelongsToProject($sprint, $project);

        $validated = $request->validate([
            'issue_id' => [
                'required',
                Rule::exists('issues', 'id')->where('project_id', $project->id),
            ],
        ]);

        $issue = Issue::findOrFail($validated['issue_id']);
        $this->assertIssueBelongsToProject($issue, $project);

        if ($issue->type === 'epic') {
            return back()->withErrors([
                'issue_id' => 'Epics stay in the backlog. Add stories, tasks, subtasks, or bugs to a sprint.',
            ]);
        }

        if ($issue->sprint_id || $issue->status !== 'backlog') {
            return back()->withErrors([
                'issue_id' => 'Only backlog issues without a sprint can be added.',
            ]);
        }

        $issue->update([
            'sprint_id' => $sprint->id,
            'status' => $issue->status === 'backlog' ? 'selected' : $issue->status,
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'added issue to sprint',
            'subject_type' => Sprint::class,
            'subject_id' => $sprint->id,
            'new_values' => [
                'sprint' => $sprint->name,
                'issue' => $issue->key,
            ],
        ]);

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Issue added to sprint.');
    }

    public function removeIssue(Request $request, Project $project, Sprint $sprint, Issue $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertSprintBelongsToProject($sprint, $project);
        $this->assertIssueBelongsToProject($issue, $project);
        abort_unless($issue->sprint_id === $sprint->id, 404);

        $issue->update([
            'sprint_id' => null,
            'status' => 'backlog',
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'removed issue from sprint',
            'subject_type' => Sprint::class,
            'subject_id' => $sprint->id,
            'old_values' => [
                'sprint' => $sprint->name,
                'issue' => $issue->key,
            ],
        ]);

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Issue returned to backlog.');
    }

    public function start(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertSprintBelongsToProject($sprint, $project);

        $activeSprint = $project->sprints()
            ->where('status', 'active')
            ->whereKeyNot($sprint->id)
            ->first();

        if (! $sprint->issues()->exists()) {
            return back()->withErrors([
                'sprint' => 'Add at least one issue before starting a sprint.',
            ]);
        }

        if ($activeSprint && ! $request->boolean('confirm_replace_active')) {
            return back()->withErrors([
                'sprint' => "Confirm before starting {$sprint->name}. {$activeSprint->name} is already active and will move back to planned.",
            ]);
        }

        $project->sprints()->where('status', 'active')->whereKeyNot($sprint->id)->update(['status' => 'planned']);
        $sprint->update(['status' => 'active']);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'started sprint',
            'subject_type' => Sprint::class,
            'subject_id' => $sprint->id,
            'new_values' => ['status' => 'active'],
        ]);

        $this->notifyProjectMembers($request, $project, 'Sprint started', "{$sprint->name} is now active.", route('projects.board.index', $project));

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint started.');
    }

    public function complete(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        $this->assertSprintBelongsToProject($sprint, $project);

        $sprint->issues()
            ->where('status', '!=', 'done')
            ->update([
                'sprint_id' => null,
                'status' => 'backlog',
            ]);

        $sprint->update(['status' => 'completed']);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'completed sprint',
            'subject_type' => Sprint::class,
            'subject_id' => $sprint->id,
            'new_values' => ['status' => 'completed'],
        ]);

        $this->notifyProjectMembers($request, $project, 'Sprint completed', "{$sprint->name} has been completed.", route('projects.sprints.index', $project));

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint completed.');
    }

    private function assertSprintBelongsToProject(Sprint $sprint, Project $project): void
    {
        abort_unless($sprint->project_id === $project->id, 404);
    }

    private function assertIssueBelongsToProject(Issue $issue, Project $project): void
    {
        abort_unless($issue->project_id === $project->id, 404);
    }

    private function notifyProjectMembers(Request $request, Project $project, string $title, string $message, string $url): void
    {
        $project->members()
            ->get()
            ->unique('id')
            ->each(fn ($user) => (new ProjectEventNotification(
                $title,
                $message,
                $url,
                $project->id,
            ))->sendTo($user));
    }
}
