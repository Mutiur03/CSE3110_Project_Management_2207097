<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Support\SqlDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IssueController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['epic', 'story', 'task', 'subtask', 'bug'])],
            'status' => ['nullable', Rule::in(['backlog', 'selected', 'in_progress', 'review', 'done'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assignee_id' => [
                'nullable',
                Rule::exists('project_members', 'user_id')->where('project_id', $project),
            ],
            'sprint_id' => [
                'nullable',
                Rule::exists('sprints', 'id')->where('project_id', $project),
            ],
        ]);

        $sql = 'SELECT i.*,
                       assignee.name AS assignee_name,
                       reporter.name AS reporter_name,
                       team.name AS team_name
                FROM issues i
                LEFT JOIN users assignee ON assignee.id = i.assignee_id
                LEFT JOIN users reporter ON reporter.id = i.reporter_id
                LEFT JOIN teams team ON team.id = i.team_id
                WHERE i.project_id = ?';
        $bindings = [$project];

        if (! empty($filters['q'])) {
            $search = '%' . trim($filters['q']) . '%';
            $sql .= ' AND (i.key LIKE ? OR i.title LIKE ?)';
            $bindings[] = $search;
            $bindings[] = $search;
        }

        if (! empty($filters['type'])) {
            $sql .= ' AND i.type = ?';
            $bindings[] = $filters['type'];
        }

        if (! empty($filters['status'])) {
            $sql .= ' AND i.status = ?';
            $bindings[] = $filters['status'];
        }

        if (! empty($filters['priority'])) {
            $sql .= ' AND i.priority = ?';
            $bindings[] = $filters['priority'];
        }

        if (! empty($filters['assignee_id'])) {
            $sql .= ' AND i.assignee_id = ?';
            $bindings[] = $filters['assignee_id'];
        }

        if (! empty($filters['sprint_id'])) {
            $sql .= ' AND i.sprint_id = ?';
            $bindings[] = $filters['sprint_id'];
        }

        $sql .= ' ORDER BY i.key';

        return view('projects.issues.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'issues' => SqlDialect::mapIssues(DB::select($sql, $bindings)),
            'members' => $this->membersWithTeamIds($project),
            'teams' => $this->projectTeams($project),
            'sprints' => $this->projectSprints($project),
            'parentIssues' => $this->parentIssues($project),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectWrite($request, $project);

        return view('projects.issues.create', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'members' => $this->membersWithTeamIds($project),
            'teams' => $this->projectTeams($project),
            'parentIssues' => $this->parentIssues($project),
        ]);
    }

    public function store(Request $request, string $project): RedirectResponse
    {
        $currentProject = $this->authorizeProjectWrite($request, $project);

        $validated = $this->validateIssue($request, $project);
        $issueId = (string) Str::uuid();
        $issueKey = $this->nextIssueKey($project, $currentProject->key);
        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO issues (
                id, project_id, team_id, sprint_id, reporter_id, assignee_id, parent_issue_id,
                key, title, description, type, status, priority, story_points,
                severity, steps_to_reproduce, expected_result, actual_result, environment,
                created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $issueId,
                $project,
                $validated['team_id'] ?? null,
                null,
                $request->user()->id,
                $validated['assignee_id'] ?? null,
                $validated['parent_issue_id'] ?? null,
                $issueKey,
                $validated['title'],
                $validated['description'] ?? null,
                $validated['type'],
                $validated['status'],
                $validated['priority'],
                $validated['story_points'] ?? null,
                $validated['severity'] ?? null,
                $validated['steps_to_reproduce'] ?? null,
                $validated['expected_result'] ?? null,
                $validated['actual_result'] ?? null,
                $validated['environment'] ?? null,
                $now,
                $now,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'created issue',
            'App\Models\Issue',
            $issueId,
            $issueId,
            newValues: [
                'key' => $issueKey,
                'title' => $validated['title'],
                'status' => $validated['status'],
            ],
        );

        if (! empty($validated['assignee_id'])) {
            $this->pushNotification(
                $validated['assignee_id'],
                'Issue assigned',
                "{$request->user()->name} assigned {$issueKey} to you.",
                route('projects.issues.show', [$project, $issueId]),
                $project,
                $issueId,
            );
        }

        return redirect()
            ->route('projects.issues.show', ['project' => $project, 'issue' => $issueId])
            ->with('status', 'Issue created.');
    }

    public function show(Request $request, string $project, string $issue): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);
        $issueRow = $this->fetchIssue($project, $issue);
        abort_if($issueRow === null, 404);

        $childIssues = $this->issuesWithChildren($project, $issue);
        $comments = $this->issueComments($issue);
        $activities = $this->issueActivities($issue);

        $issueRow->childIssues = $childIssues;
        $issueRow->comments = $comments;

        return view('projects.issues.show', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'issue' => $issueRow,
            'activities' => $activities,
            'canWrite' => $currentProject->can_write,
            'hasChildIssues' => $childIssues->isNotEmpty(),
            'members' => $this->membersWithTeamIds($project),
            'teams' => $this->projectTeams($project),
            'parentIssues' => $this->parentIssues($project, $issue),
        ]);
    }

    public function update(Request $request, string $project, string $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $issueRow = $this->fetchIssue($project, $issue);
        abort_if($issueRow === null, 404);

        $validated = $this->validateIssue($request, $project, $issueRow);
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
        $oldValues = [];
        foreach ($trackedFields as $field) {
            $oldValues[$field] = $issueRow->{$field} ?? null;
        }
        $oldAssigneeId = $issueRow->assignee_id;
        $oldStatus = $issueRow->status;

        if ($validated['status'] !== $oldStatus) {
            SqlDialect::updateIssueStatus($issue, $validated['status']);
        }

        DB::update(
            'UPDATE issues
             SET title = ?, description = ?, type = ?, priority = ?,
                 assignee_id = ?, team_id = ?, parent_issue_id = ?, story_points = ?,
                 severity = ?, steps_to_reproduce = ?, expected_result = ?,
                 actual_result = ?, environment = ?, updated_at = ?
             WHERE id = ? AND project_id = ?',
            [
                $validated['title'],
                $validated['description'] ?? null,
                $validated['type'],
                $validated['priority'],
                $validated['assignee_id'] ?? null,
                $validated['team_id'] ?? null,
                $validated['parent_issue_id'] ?? null,
                $validated['story_points'] ?? null,
                $validated['severity'] ?? null,
                $validated['steps_to_reproduce'] ?? null,
                $validated['expected_result'] ?? null,
                $validated['actual_result'] ?? null,
                $validated['environment'] ?? null,
                now()->toDateTimeString(),
                $issue,
                $project,
            ],
        );

        $newValues = [];
        foreach ($trackedFields as $field) {
            $newValues[$field] = $validated[$field] ?? null;
        }

        $this->logActivity(
            $project,
            $request->user()->id,
            'updated issue',
            'App\Models\Issue',
            $issue,
            $issue,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        if (! empty($validated['assignee_id']) && $validated['assignee_id'] !== $oldAssigneeId) {
            $this->pushNotification(
                $validated['assignee_id'],
                'Issue assigned',
                "{$request->user()->name} assigned {$issueRow->key} to you.",
                route('projects.issues.show', [$project, $issue]),
                $project,
                $issue,
            );
        }

        if ($validated['status'] !== $oldStatus) {
            $this->notifyIssueWatchers(
                $issueRow,
                'Issue status changed',
                "{$issueRow->key} moved from {$oldStatus} to {$validated['status']}.",
                route('projects.issues.show', [$project, $issue]),
                $project,
                $issue,
            );
        }

        return redirect()
            ->route('projects.issues.show', ['project' => $project, 'issue' => $issue])
            ->with('status', 'Issue updated.');
    }

    public function destroy(Request $request, string $project, string $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $issueRow = $this->fetchIssue($project, $issue);
        abort_if($issueRow === null, 404);

        if (DB::selectOne(
            'SELECT 1 AS found FROM issues WHERE parent_issue_id = ?',
            [$issue],
        ) !== null) {
            return redirect()
                ->route('projects.issues.show', [$project, $issue])
                ->withErrors([
                    'issue' => 'Remove or reassign child issues before deleting this issue.',
                ]);
        }

        $deletedIssue = [
            'id' => $issueRow->id,
            'key' => $issueRow->key,
            'title' => $issueRow->title,
            'type' => $issueRow->type,
        ];

        $this->logActivity(
            $project,
            $request->user()->id,
            'deleted issue',
            'App\Models\Issue',
            $issue,
            oldValues: $deletedIssue,
        );

        DB::delete('DELETE FROM issues WHERE id = ? AND project_id = ?', [$issue, $project]);

        return redirect()
            ->route('projects.issues.index', $project)
            ->with('status', "{$deletedIssue['key']} deleted.");
    }

    private function fetchIssue(string $projectId, string $issueId): ?object
    {
        return SqlDialect::normalizeIssue(DB::selectOne(
            'SELECT * FROM v_issue_full WHERE project_id = ? AND id = ?',
            [$projectId, $issueId],
        ));
    }

    private function issuesWithChildren(string $projectId, string $parentIssueId): Collection
    {
        $children = SqlDialect::mapIssues(DB::select(
            'SELECT * FROM v_issue_full
             WHERE project_id = ? AND parent_issue_id = ?
             ORDER BY key',
            [$projectId, $parentIssueId],
        ));

        $childIds = $children->pluck('id')->all();

        if ($childIds === []) {
            return $children;
        }

        $placeholders = implode(', ', array_fill(0, count($childIds), '?'));
        $grandchildren = SqlDialect::mapIssues(DB::select(
            "SELECT * FROM v_issue_full
             WHERE project_id = ? AND parent_issue_id IN ({$placeholders})
             ORDER BY key",
            array_merge([$projectId], $childIds),
        ))->groupBy('parent_issue_id');

        $children->each(function ($child) use ($grandchildren) {
            $child->childIssues = $grandchildren->get($child->id, collect());
        });

        return $children;
    }

    private function issueComments(string $issueId): Collection
    {
        return SqlDialect::mapComments(DB::select(
            'SELECT c.*, u.name AS user_name
             FROM comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.issue_id = ?
             ORDER BY c.created_at DESC',
            [$issueId],
        ));
    }

    private function issueActivities(string $issueId): Collection
    {
        return SqlDialect::mapActivities(DB::select(
            'SELECT al.*, u.name AS user_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.issue_id = ?
             ORDER BY al.created_at DESC',
            [$issueId],
        ));
    }

    private function membersWithTeamIds(string $projectId): Collection
    {
        return SqlDialect::mapMembersWithTeamIds(DB::select(
            'SELECT u.id, u.name, u.email,
                    '.SqlDialect::groupConcat('t.id').'
             FROM users u
             INNER JOIN project_members pm ON pm.user_id = u.id
             LEFT JOIN team_members tm ON tm.user_id = u.id
             LEFT JOIN teams t ON t.id = tm.team_id AND t.project_id = ?
             WHERE pm.project_id = ?
             GROUP BY u.id, u.name, u.email
             ORDER BY u.name',
            [$projectId, $projectId],
        ));
    }

    private function projectTeams(string $projectId): Collection
    {
        return collect(DB::select(
            'SELECT id, name FROM teams WHERE project_id = ? ORDER BY name',
            [$projectId],
        ));
    }

    private function projectSprints(string $projectId): Collection
    {
        return SqlDialect::mapSprints(DB::select(
            "SELECT s.*
             FROM sprints s
             WHERE s.project_id = ?
             ORDER BY CASE s.status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END, s.created_at DESC",
            [$projectId],
        ));
    }

    private function parentIssues(string $projectId, ?string $excludeIssueId = null): Collection
    {
        $sql = "SELECT id, key, title, type FROM issues
                WHERE project_id = ? AND type IN ('epic', 'story', 'task')";
        $bindings = [$projectId];

        if ($excludeIssueId !== null) {
            $sql .= ' AND id != ?';
            $bindings[] = $excludeIssueId;
        }

        $sql .= ' ORDER BY key';

        return collect(DB::select($sql, $bindings));
    }

    private function nextIssueKey(string $projectId, string $projectKey): string
    {
        $row = DB::selectOne(
            SqlDialect::maxIssueNumberSql(),
            [$projectKey, $projectId, $projectKey . '-%'],
        );

        $lastNumber = (int) ($row->last_number ?? 0);

        return $projectKey . '-' . ($lastNumber + 1);
    }

    private function rules(string $projectId, ?string $issueId = null, ?string $type = null): array
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
                Rule::exists('project_members', 'user_id')->where('project_id', $projectId),
            ],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('project_id', $projectId),
            ],
            'parent_issue_id' => [
                Rule::requiredIf(in_array($type, ['story', 'subtask'], true)),
                'nullable',
                Rule::exists('issues', 'id')->where('project_id', $projectId),
                Rule::notIn([$issueId]),
            ],
        ];
    }

    private function validateIssue(Request $request, string $projectId, ?object $issue = null): array
    {
        $validated = $request->validate($this->rules($projectId, $issue?->id, $request->input('type')));

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
            $parentIssue = DB::selectOne(
                'SELECT type FROM issues WHERE project_id = ? AND id = ?',
                [$projectId, $validated['parent_issue_id']],
            );

            abort_if($parentIssue === null, 404);

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
            && DB::selectOne(
                'SELECT 1 AS found FROM team_members WHERE team_id = ? AND user_id = ?',
                [$validated['team_id'], $validated['assignee_id']],
            ) === null
        ) {
            throw ValidationException::withMessages([
                'assignee_id' => 'The assignee must belong to the selected team.',
            ]);
        }

        return $validated;
    }

    private function notifyIssueWatchers(
        object $issue,
        string $title,
        string $message,
        string $url,
        string $projectId,
        string $issueId,
    ): void {
        $userIds = collect([$issue->reporter_id, $issue->assignee_id])->filter()->unique()->values()->all();

        foreach ($userIds as $userId) {
            $this->pushNotification($userId, $title, $message, $url, $projectId, $issueId);
        }
    }
}
