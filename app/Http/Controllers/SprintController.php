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

class SprintController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);

        $sprints = SqlDialect::mapSprints(DB::select(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM issues i WHERE i.sprint_id = s.id) AS issues_count
             FROM sprints s
             WHERE s.project_id = ?
             ORDER BY CASE s.status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END, s.created_at DESC",
            [$project],
        ));

        $sprintIds = $sprints->pluck('id')->all();
        $issuesBySprint = collect();

        if ($sprintIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($sprintIds), '?'));
            $issuesBySprint = SqlDialect::mapIssues(DB::select(
                "SELECT i.*,
                        assignee.name AS assignee_name,
                        team.name AS team_name
                 FROM issues i
                 LEFT JOIN users assignee ON assignee.id = i.assignee_id
                 LEFT JOIN teams team ON team.id = i.team_id
                 WHERE i.sprint_id IN ({$placeholders})
                 ORDER BY i.key",
                $sprintIds,
            ))->groupBy('sprint_id');
        }

        $sprints->each(function ($sprint) use ($issuesBySprint) {
            $sprint->issues = $issuesBySprint->get($sprint->id, collect());
        });

        $backlogIssues = SqlDialect::mapIssues(DB::select(
            "SELECT i.*,
                    assignee.name AS assignee_name,
                    team.name AS team_name
             FROM issues i
             LEFT JOIN users assignee ON assignee.id = i.assignee_id
             LEFT JOIN teams team ON team.id = i.team_id
             WHERE i.project_id = ?
               AND i.sprint_id IS NULL
               AND i.status = 'backlog'
               AND i.type IN ('story', 'task', 'subtask', 'bug')
             ORDER BY i.key",
            [$project],
        ));

        return view('projects.sprints.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'sprints' => $sprints,
            'backlogIssues' => $backlogIssues,
        ]);
    }

    public function store(Request $request, string $project): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $sprintId = (string) Str::uuid();
        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO sprints (id, project_id, name, goal, start_date, end_date, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $sprintId,
                $project,
                $validated['name'],
                $validated['goal'] ?? null,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                'planned',
                $now,
                $now,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'created sprint',
            'App\Models\Sprint',
            $sprintId,
            newValues: [
                'name' => $validated['name'],
                'status' => 'planned',
            ],
        );

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint created.');
    }

    public function update(Request $request, string $project, string $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $sprintRow = SqlDialect::normalizeSprint(DB::selectOne('SELECT * FROM sprints WHERE id = ? AND project_id = ?', [$sprint, $project]));
        abort_if($sprintRow === null, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $oldValues = [
            'name' => $sprintRow->name,
            'goal' => $sprintRow->goal,
            'start_date' => $sprintRow->start_date,
            'end_date' => $sprintRow->end_date,
        ];

        $newValues = [
            'name' => $validated['name'],
            'goal' => $validated['goal'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];

        DB::update(
            'UPDATE sprints SET name = ?, goal = ?, start_date = ?, end_date = ?, updated_at = ? WHERE id = ?',
            [
                $newValues['name'],
                $newValues['goal'],
                $newValues['start_date'],
                $newValues['end_date'],
                now()->toDateTimeString(),
                $sprint,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'updated sprint',
            'App\Models\Sprint',
            $sprint,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint updated.');
    }

    public function addIssue(Request $request, string $project, string $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $sprintRow = DB::selectOne('SELECT name FROM sprints WHERE id = ? AND project_id = ?', [$sprint, $project]);
        abort_if($sprintRow === null, 404);

        $validated = $request->validate([
            'issue_id' => [
                'required',
                Rule::exists('issues', 'id')->where('project_id', $project),
            ],
        ]);

        $issue = DB::selectOne(
            'SELECT id, key, type, sprint_id, status FROM issues WHERE id = ? AND project_id = ?',
            [$validated['issue_id'], $project],
        );
        abort_if($issue === null, 404);

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

        $newStatus = $issue->status === 'backlog' ? 'selected' : $issue->status;
        $now = now()->toDateTimeString();

        DB::update(
            'UPDATE issues SET sprint_id = ?, updated_at = ? WHERE id = ?',
            [$sprint, $now, $issue->id],
        );
        SqlDialect::updateIssueStatus($issue->id, $newStatus);

        $this->logActivity(
            $project,
            $request->user()->id,
            'added issue to sprint',
            'App\Models\Sprint',
            $sprint,
            $issue->id,
            newValues: [
                'sprint' => $sprintRow->name,
                'issue' => $issue->key,
            ],
        );

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Issue added to sprint.');
    }

    public function removeIssue(Request $request, string $project, string $sprint, string $issue): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $sprintRow = DB::selectOne('SELECT name FROM sprints WHERE id = ? AND project_id = ?', [$sprint, $project]);
        abort_if($sprintRow === null, 404);

        $issueRow = DB::selectOne(
            'SELECT key, sprint_id FROM issues WHERE id = ? AND project_id = ?',
            [$issue, $project],
        );
        abort_if($issueRow === null || $issueRow->sprint_id !== $sprint, 404);

        $now = now()->toDateTimeString();

        DB::update(
            'UPDATE issues SET sprint_id = NULL, updated_at = ? WHERE id = ?',
            [$now, $issue],
        );
        SqlDialect::updateIssueStatus($issue, 'backlog');

        $this->logActivity(
            $project,
            $request->user()->id,
            'removed issue from sprint',
            'App\Models\Sprint',
            $sprint,
            $issue,
            oldValues: [
                'sprint' => $sprintRow->name,
                'issue' => $issueRow->key,
            ],
        );

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Issue returned to backlog.');
    }

    public function start(Request $request, string $project, string $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $sprintRow = DB::selectOne('SELECT name FROM sprints WHERE id = ? AND project_id = ?', [$sprint, $project]);
        abort_if($sprintRow === null, 404);

        $activeSprint = DB::selectOne(
            "SELECT name FROM sprints WHERE project_id = ? AND status = 'active' AND id != ?",
            [$project, $sprint],
        );

        if (DB::selectOne('SELECT 1 AS found FROM issues WHERE sprint_id = ?', [$sprint]) === null) {
            return back()->withErrors([
                'sprint' => 'Add at least one issue before starting a sprint.',
            ]);
        }

        if ($activeSprint && ! $request->boolean('confirm_replace_active')) {
            return back()->withErrors([
                'sprint' => "Confirm before starting {$sprintRow->name}. {$activeSprint->name} is already active and will move back to planned.",
            ]);
        }

        $now = now()->toDateTimeString();

        DB::update(
            "UPDATE sprints SET status = 'planned', updated_at = ? WHERE project_id = ? AND status = 'active' AND id != ?",
            [$now, $project, $sprint],
        );

        DB::update(
            "UPDATE sprints SET status = 'active', updated_at = ? WHERE id = ?",
            [$now, $sprint],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'started sprint',
            'App\Models\Sprint',
            $sprint,
            newValues: ['status' => 'active'],
        );

        $this->notifyProjectMembers(
            $project,
            'Sprint started',
            "{$sprintRow->name} is now active.",
            route('projects.board.index', $project),
        );

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint started.');
    }

    public function complete(Request $request, string $project, string $sprint): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $sprintRow = DB::selectOne('SELECT name FROM sprints WHERE id = ? AND project_id = ?', [$sprint, $project]);
        abort_if($sprintRow === null, 404);

        $now = now()->toDateTimeString();

        $incompleteIssues = DB::select(
            "SELECT id FROM issues WHERE sprint_id = ? AND status != 'done'",
            [$sprint],
        );

        foreach ($incompleteIssues as $incompleteIssue) {
            DB::update(
                'UPDATE issues SET sprint_id = NULL, updated_at = ? WHERE id = ?',
                [$now, $incompleteIssue->id],
            );
            SqlDialect::updateIssueStatus($incompleteIssue->id, 'backlog');
        }

        DB::update(
            "UPDATE sprints SET status = 'completed', updated_at = ? WHERE id = ?",
            [$now, $sprint],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'completed sprint',
            'App\Models\Sprint',
            $sprint,
            newValues: ['status' => 'completed'],
        );

        $this->notifyProjectMembers(
            $project,
            'Sprint completed',
            "{$sprintRow->name} has been completed.",
            route('projects.sprints.index', $project),
        );

        return redirect()
            ->route('projects.sprints.index', $project)
            ->with('status', 'Sprint completed.');
    }

    private function notifyProjectMembers(string $projectId, string $title, string $message, string $url): void
    {
        $members = DB::select(
            'SELECT DISTINCT u.id
             FROM users u
             INNER JOIN project_members pm ON pm.user_id = u.id
             WHERE pm.project_id = ?',
            [$projectId],
        );

        foreach ($members as $member) {
            $this->pushNotification($member->id, $title, $message, $url, $projectId);
        }
    }
}
