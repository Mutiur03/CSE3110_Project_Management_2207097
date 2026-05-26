<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Project;
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

        $issues = $project->issues()
            ->with(['assignee', 'team'])
            ->latest()
            ->get();

        return view('projects.issues.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'issues' => $issues,
            'members' => $this->projectMembersWithTeams($project),
            'teams' => $project->teams()->orderBy('name')->get(),
            'parentIssues' => $project->issues()->whereIn('type', ['epic', 'story'])->orderBy('key')->get(),
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
            'parentIssues' => $project->issues()->whereIn('type', ['epic', 'story'])->orderBy('key')->get(),
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
            'issue' => $issue->load(['reporter', 'assignee', 'team', 'parentIssue', 'comments.user']),
            'members' => $this->projectMembersWithTeams($project),
            'teams' => $project->teams()->orderBy('name')->get(),
            'parentIssues' => $project->issues()
                ->whereIn('type', ['epic', 'story'])
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
        $oldValues = $issue->only(['title', 'type', 'status', 'priority', 'assignee_id', 'team_id', 'story_points']);

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
            'new_values' => $issue->only(['title', 'type', 'status', 'priority', 'assignee_id', 'team_id', 'story_points']),
        ]);

        return redirect()
            ->route('projects.issues.show', ['project' => $project, 'issue' => $issue])
            ->with('status', 'Issue updated.');
    }

    private function rules(Project $project, ?Issue $issue = null): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:4000'],
            'type' => ['required', Rule::in(['epic', 'story', 'task', 'bug'])],
            'status' => ['required', Rule::in(['backlog', 'selected', 'in_progress', 'review', 'done'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'story_points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'assignee_id' => [
                'nullable',
                Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
            ],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('project_id', $project->id),
            ],
            'parent_issue_id' => [
                'nullable',
                Rule::exists('issues', 'id')->where('project_id', $project->id),
                Rule::notIn([$issue?->id]),
            ],
        ];
    }

    private function validateIssue(Request $request, Project $project, ?Issue $issue = null): array
    {
        $validated = $request->validate($this->rules($project, $issue));

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
}
