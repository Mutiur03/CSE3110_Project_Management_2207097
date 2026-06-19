<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        $projects = $this->userProjects($request);

        return view('projects.teams.index', [
            'projects' => $projects,
            'currentProject' => $project,
            'teams' => $project->teams()
                ->with(['members' => fn ($query) => $query->orderBy('name')])
                ->withCount(['members', 'issues'])
                ->orderBy('name')
                ->get(),
            'projectMembers' => $project->members()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('teams', 'name')->where('project_id', $project->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team = Team::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'created team',
            'subject_type' => Team::class,
            'subject_id' => $team->id,
            'new_values' => [
                'name' => $team->name,
            ],
        ]);

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', 'Team created.');
    }

    public function addMember(Request $request, Project $project, Team $team): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        abort_unless($team->project_id === $project->id, 404);

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
            ],
            'role' => ['required', 'string', 'max:60'],
        ]);

        $existingMember = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($existingMember) {
            DB::table('team_members')
                ->where('team_id', $team->id)
                ->where('user_id', $validated['user_id'])
                ->update([
                    'role' => $validated['role'],
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('team_members')->insert([
                'id' => (string) Str::uuid(),
                'team_id' => $team->id,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'],
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'added team member',
            'subject_type' => Team::class,
            'subject_id' => $team->id,
            'new_values' => [
                'team' => $team->name,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'],
            ],
        ]);

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', 'Team member added.');
    }

    public function removeMember(Request $request, Project $project, Team $team, User $user): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        abort_unless($team->project_id === $project->id, 404);
        abort_unless($team->members()->where('users.id', $user->id)->exists(), 404);

        DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->delete();

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'removed team member',
            'subject_type' => Team::class,
            'subject_id' => $team->id,
            'old_values' => [
                'team' => $team->name,
                'member' => $user->email,
            ],
        ]);

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', 'Team member removed.');
    }

    public function destroy(Request $request, Project $project, Team $team): RedirectResponse
    {
        $this->authorizeProjectWrite($request, $project);
        abort_unless($team->project_id === $project->id, 404);

        $teamName = $team->name;
        $issuesUnassigned = $team->issues()->count();

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'deleted team',
            'subject_type' => Team::class,
            'subject_id' => $team->id,
            'old_values' => [
                'name' => $teamName,
                'issues_unassigned' => $issuesUnassigned,
            ],
        ]);

        $team->delete();

        return redirect()
            ->route('projects.teams.index', $project)
            ->with('status', "{$teamName} deleted.");
    }
}
