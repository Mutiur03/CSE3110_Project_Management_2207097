<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Project;
use App\Notifications\ProjectEventNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['epic', 'story', 'task', 'subtask', 'bug'])],
            'status' => ['nullable', Rule::in(['backlog', 'selected', 'in_progress', 'review', 'done'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assignee_id' => [
                'nullable',
                Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
            ],
            'sprint_id' => [
                'nullable',
                Rule::exists('sprints', 'id')->where('project_id', $project->id),
            ],
        ]);

        $issuesQuery = $project->issues()
            ->with(['assignee', 'reporter', 'team', 'parentIssue'])
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $search = trim($search);

                $query->where(function ($query) use ($search) {
                    $query->where('key', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($filters['assignee_id'] ?? null, fn ($query, string $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->when($filters['sprint_id'] ?? null, fn ($query, string $sprintId) => $query->where('sprint_id', $sprintId));

        $issues = $issuesQuery->orderBy('key')->get();

        return view('projects.issues.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'issues' => $issues,
            'members' => $this->projectMembersWithTeams($project),
            'teams' => $project->teams()->orderBy('name')->get(),
            'sprints' => $project->sprints()->orderByRaw("case status when 'active' then 0 when 'planned' then 1 else 2 end")->latest()->get(),
            'parentIssues' => $project->issues()->whereIn('type', ['epic', 'story', 'task'])->orderBy('key')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        return view('projects.issues.create', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'members' => $this->projectMembersWithTeams($project),
            'teams' => $project->teams()->orderBy('name')->get(),
            'parentIssues' => $project->issues()->whereIn('type', ['epic', 'story', 'task'])->orderBy('key')->get(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($request, $project);

        $validated = $this->validateIssue($request, $project);

        $issue = Issue::create([
            ...$validated,
            'project_id' => $project->id,
            'reporter_id' => $request->user()->id,
            'key' => $this->nextIssueKey($project),
            'team_id' => $validated['team_id'] ?? null,
            'assignee_id' => $validated['assignee_id'] ?? null,
            'parent_issue_id' => $validated['parent_issue_id'] ?? null,
            'story_points' => $validated['story_points'] ?? null,
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'created issue',
            'subject_type' => Issue::class,
            'subject_id' => $issue->id,
            'new_values' => [
                'key' => $issue->key,
                'title' => $issue->title,
                'status' => $issue->status,
            ],
        ]);

        $this->notifyAssignee($request, $project, $issue, 'Issue assigned', "{$request->user()->name} assigned {$issue->key} to you.");

        return redirect()
            ->route('projects.issues.show', ['project' => $project, 'issue' => $issue])
            ->with('status', 'Issue created.');
    }

    public function show(Request $request, Project $project, Issue $issue): View
    {
        $this->authorizeProjectAccess($request, $project);
        $this->assertIssueBelongsToProject($issue, $project);

        return view('projects.issues.show', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'issue' => $issue->load([
                'reporter',
                'assignee',
                'team',
                'parentIssue',
                'comments.user',
                'activityLogs.user',
                'activityLogs.issue',
                'childIssues' => fn ($query) => $query
                    ->with(['reporter', 'assignee', 'team'])
                    ->orderBy('key'),
                'childIssues.childIssues' => fn ($query) => $query
                    ->with(['reporter', 'assignee', 'team'])
                    ->orderBy('key'),
            ]),
            'members' => $this->projectMembersWithTeams($project),
            'teams' => $project->teams()->orderBy('name')->get(),
            'parentIssues' => $project->issues()
                ->whereIn('type', ['epic', 'story', 'task'])
                ->where('id', '!=', $issue->id)
                ->orderBy('key')
                ->get(),
        ]);
    }

    public function update(Request $request, Project $project, Issue $issue): RedirectResponse
    {
        $this->authorizeProjectAccess($request, $project);
        $this->assertIssueBelongsToProject($issue, $project);

        $validated = $this->validateIssue($request, $project, $issue);
        $trackedFields = [
            'title',
            'type',
            'status',
            'priority',
            'assignee_id',
            'team_id',
            'story_points',
            'severity',
            'steps_to_reproduce',
            'expected_result',
            'actual_result',
            'environment',
        ];
        $oldValues = $issue->only($trackedFields);
        $oldAssigneeId = $issue->assignee_id;
        $oldStatus = $issue->status;

        $issue->update([
            ...$validated,
            'team_id' => $validated['team_id'] ?? null,
            'assignee_id' => $validated['assignee_id'] ?? null,
            'parent_issue_id' => $validated['parent_issue_id'] ?? null,
            'story_points' => $validated['story_points'] ?? null,
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action' => 'updated issue',
            'subject_type' => Issue::class,
            'subject_id' => $issue->id,
            'old_values' => $oldValues,
            'new_values' => $issue->only($trackedFields),
        ]);

        if ($issue->assignee_id && $issue->assignee_id !== $oldAssigneeId) {
            $this->notifyAssignee($request, $project, $issue, 'Issue assigned', "{$request->user()->name} assigned {$issue->key} to you.");
        }

        if ($issue->status !== $oldStatus) {
            $this->notifyIssueWatchers($request, $project, $issue, 'Issue status changed', "{$issue->key} moved from {$oldStatus} to {$issue->status}.");
        }

        return redirect()
            ->route('projects.issues.show', ['project' => $project, 'issue' => $issue])
            ->with('status', 'Issue updated.');
    }

    private function rules(Project $project, ?Issue $issue = null, ?string $type = null): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:4000'],
            'type' => ['required', Rule::in(['epic', 'story', 'task', 'subtask', 'bug'])],
            'status' => ['required', Rule::in(['backlog', 'selected', 'in_progress', 'review', 'done'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'story_points' => [Rule::requiredIf(in_array($type, ['story', 'task'], true)), 'nullable', 'integer', 'min:1', 'max:100'],
            'severity' => [Rule::requiredIf($type === 'bug'), 'nullable', Rule::in(['minor', 'major', 'critical', 'blocker'])],
            'steps_to_reproduce' => [Rule::requiredIf($type === 'bug'), 'nullable', 'string', 'max:4000'],
            'expected_result' => [Rule::requiredIf($type === 'bug'), 'nullable', 'string', 'max:2000'],
            'actual_result' => [Rule::requiredIf($type === 'bug'), 'nullable', 'string', 'max:2000'],
            'environment' => [Rule::requiredIf($type === 'bug'), 'nullable', 'string', 'max:180'],
            'assignee_id' => [
                'nullable',
                Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
            ],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('project_id', $project->id),
            ],
            'parent_issue_id' => [
                Rule::requiredIf(in_array($type, ['story', 'subtask'], true)),
                'nullable',
                Rule::exists('issues', 'id')->where('project_id', $project->id),
                Rule::notIn([$issue?->id]),
            ],
        ];
    }

    private function validateIssue(Request $request, Project $project, ?Issue $issue = null): array
    {
        $validated = $request->validate($this->rules($project, $issue, $request->input('type')));

        if (in_array($validated['type'], ['epic', 'task', 'bug'], true)) {
            $validated['parent_issue_id'] = null;
        }

        if (in_array($validated['type'], ['epic', 'subtask', 'bug'], true)) {
            $validated['story_points'] = null;
        }

        if ($validated['type'] !== 'bug') {
            $validated['severity'] = null;
            $validated['steps_to_reproduce'] = null;
            $validated['expected_result'] = null;
            $validated['actual_result'] = null;
            $validated['environment'] = null;
        }

        if (! empty($validated['parent_issue_id'])) {
            $parentIssue = Issue::query()
                ->where('project_id', $project->id)
                ->findOrFail($validated['parent_issue_id']);

            if ($validated['type'] === 'story' && $parentIssue->type !== 'epic') {
                throw ValidationException::withMessages([
                    'parent_issue_id' => 'A story can only be linked under an epic.',
                ]);
            }

            if ($validated['type'] === 'subtask' && ! in_array($parentIssue->type, ['story', 'task'], true)) {
                throw ValidationException::withMessages([
                    'parent_issue_id' => 'A subtask can only be linked under a story or task.',
                ]);
            }
        }

        if (
            ! empty($validated['team_id'])
            && ! empty($validated['assignee_id'])
            && ! DB::table('team_members')
                ->where('team_id', $validated['team_id'])
                ->where('user_id', $validated['assignee_id'])
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'assignee_id' => 'The assignee must belong to the selected team.',
            ]);
        }

        return $validated;
    }

    private function nextIssueKey(Project $project): string
    {
        $lastNumber = $project->issues()
            ->where('key', 'like', $project->key . '-%')
            ->get()
            ->map(fn (Issue $issue) => (int) str_replace($project->key . '-', '', $issue->key))
            ->max() ?? 0;

        return $project->key . '-' . ($lastNumber + 1);
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        abort_unless(
            $project->owner_id === $request->user()->id
                || $project->members()->where('users.id', $request->user()->id)->exists(),
            403
        );
    }

    private function assertIssueBelongsToProject(Issue $issue, Project $project): void
    {
        abort_unless($issue->project_id === $project->id, 404);
    }

    private function userProjects(Request $request)
    {
        return Project::query()
            ->where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->orderBy('name')
            ->get();
    }

    private function projectMembersWithTeams(Project $project)
    {
        return $project->members()
            ->with(['teams' => fn ($query) => $query->where('project_id', $project->id)])
            ->orderBy('name')
            ->get();
    }

    private function notifyAssignee(Request $request, Project $project, Issue $issue, string $title, string $message): void
    {
        $issue->loadMissing('assignee');

        if ($issue->assignee && ! $issue->assignee->is($request->user())) {
            $issue->assignee->notify(new ProjectEventNotification(
                $title,
                $message,
                route('projects.issues.show', [$project, $issue]),
                $project->id,
                $issue->id,
            ));
        }
    }

    private function notifyIssueWatchers(Request $request, Project $project, Issue $issue, string $title, string $message): void
    {
        $issue->loadMissing(['reporter', 'assignee']);

        collect([$issue->reporter, $issue->assignee])
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $user->is($request->user()))
            ->each(fn ($user) => $user->notify(new ProjectEventNotification(
                $title,
                $message,
                route('projects.issues.show', [$project, $issue]),
                $project->id,
                $issue->id,
            )));
    }
}
