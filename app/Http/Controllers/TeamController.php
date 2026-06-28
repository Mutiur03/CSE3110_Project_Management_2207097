<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Support\SqlDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);

        $teams = SqlDialect::mapTeams(DB::select(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id) AS members_count,
                    (SELECT COUNT(*) FROM issues i WHERE i.team_id = t.id) AS issues_count
             FROM teams t
             WHERE t.project_id = ?
             ORDER BY t.name',
            [$project],
        ));

        $teamIds = $teams->pluck('id')->all();
        $membersByTeam = collect();

        if ($teamIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($teamIds), '?'));
            $memberRows = DB::select(
                "SELECT tm.team_id, tm.role, u.id, u.name, u.email
                 FROM team_members tm
                 INNER JOIN users u ON u.id = tm.user_id
                 WHERE tm.team_id IN ({$placeholders})
                 ORDER BY u.name",
                $teamIds,
            );

            $membersByTeam = collect($memberRows)->groupBy('team_id');
        }

        $teams->each(function ($team) use ($membersByTeam) {
            $team->members = $membersByTeam->get($team->id, collect());
        });

        $projectMembers = collect(DB::select(
            'SELECT u.id, u.name, u.email
             FROM users u
             INNER JOIN project_members pm ON pm.user_id = u.id
             WHERE pm.project_id = ?
             ORDER BY u.name',
            [$project],
        ));

        return view('projects.teams.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'teams' => $teams,
            'projectMembers' => $projectMembers,
        ]);
    }

    public function store(Request $request, string $project): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('teams', 'name')->where('project_id', $project),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $teamId = (string) Str::uuid();
        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO teams (id, project_id, name, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $teamId,
                $project,
                $validated['name'],
                $validated['description'] ?? null,
                $now,
                $now,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'created team',
            'App\Models\Team',
            $teamId,
            newValues: [
                'name' => $validated['name'],
            ],
        );

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', 'Team created.');
    }

    public function addMember(Request $request, string $project, string $team): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $teamRow = DB::selectOne('SELECT id, name, project_id FROM teams WHERE id = ?', [$team]);
        abort_if($teamRow === null || $teamRow->project_id !== $project, 404);

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('project_members', 'user_id')->where('project_id', $project),
            ],
            'role' => ['required', Rule::in(['project_owner', 'scrum_master', 'developer', 'viewer', 'admin'])],
        ]);

        $now = now()->toDateTimeString();

        if (DB::selectOne(
            'SELECT 1 AS found FROM team_members WHERE team_id = ? AND user_id = ?',
            [$team, $validated['user_id']],
        ) !== null) {
            DB::update(
                'UPDATE team_members SET role = ?, updated_at = ? WHERE team_id = ? AND user_id = ?',
                [$validated['role'], $now, $team, $validated['user_id']],
            );
        } else {
            DB::insert(
                'INSERT INTO team_members (id, team_id, user_id, role, joined_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    (string) Str::uuid(),
                    $team,
                    $validated['user_id'],
                    $validated['role'],
                    $now,
                    $now,
                    $now,
                ],
            );
        }

        $this->logActivity(
            $project,
            $request->user()->id,
            'added team member',
            'App\Models\Team',
            $team,
            newValues: [
                'team' => $teamRow->name,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'],
            ],
        );

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', 'Team member added.');
    }

    public function removeMember(Request $request, string $project, string $team, string $user): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $teamRow = DB::selectOne('SELECT name, project_id FROM teams WHERE id = ?', [$team]);
        abort_if($teamRow === null || $teamRow->project_id !== $project, 404);

        abort_if(DB::selectOne(
            'SELECT 1 AS found FROM team_members WHERE team_id = ? AND user_id = ?',
            [$team, $user],
        ) === null, 404);

        $member = DB::selectOne('SELECT email FROM users WHERE id = ?', [$user]);

        DB::delete('DELETE FROM team_members WHERE team_id = ? AND user_id = ?', [$team, $user]);

        $this->logActivity(
            $project,
            $request->user()->id,
            'removed team member',
            'App\Models\Team',
            $team,
            oldValues: [
                'team' => $teamRow->name,
                'member' => $member->email ?? $user,
            ],
        );

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', 'Team member removed.');
    }

    public function destroy(Request $request, string $project, string $team): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $teamRow = DB::selectOne('SELECT name, project_id FROM teams WHERE id = ?', [$team]);
        abort_if($teamRow === null || $teamRow->project_id !== $project, 404);

        $issuesUnassigned = (int) (DB::selectOne(
            'SELECT COUNT(*) AS total FROM issues WHERE team_id = ?',
            [$team],
        )->total ?? 0);

        $this->logActivity(
            $project,
            $request->user()->id,
            'deleted team',
            'App\Models\Team',
            $team,
            oldValues: [
                'name' => $teamRow->name,
                'issues_unassigned' => $issuesUnassigned,
            ],
        );

        DB::delete('DELETE FROM teams WHERE id = ?', [$team]);

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', "{$teamRow->name} deleted.");
    }
}
