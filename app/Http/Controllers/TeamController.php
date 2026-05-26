<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
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
        $this->authorizeProjectAccess($request, $project);

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
        $this->authorizeProjectAccess($request, $project);
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
