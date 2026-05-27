<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        return view('projects.members.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'members' => $project->members()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectManagement($request, $project);

        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(['project_owner', 'scrum_master', 'developer', 'viewer'])],
        ], [
            'email.exists' => 'That email is not registered yet. Ask the user to create an account first, then add them to the project.',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if ($project->members()->where('users.id', $user->id)->exists()) {
            return back()
                ->withErrors(['email' => 'This user is already a project member.'])
                ->withInput();
        }

        DB::table('project_members')->insert([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'added project member',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'new_values' => [
                'member' => $user->email,
                'role' => $validated['role'],
            ],
        ]);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Project member added.');
    }

    public function update(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorizeProjectManagement($request, $project);
        abort_unless($project->members()->where('users.id', $user->id)->exists(), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['project_owner', 'scrum_master', 'developer', 'viewer'])],
        ]);

        DB::table('project_members')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->update([
                'role' => $validated['role'],
                'updated_at' => now(),
            ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'updated project member role',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'new_values' => [
                'member' => $user->email,
                'role' => $validated['role'],
            ],
        ]);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Project member updated.');
    }

    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorizeProjectManagement($request, $project);
        abort_unless($project->members()->where('users.id', $user->id)->exists(), 404);

        if ($project->owner_id === $user->id) {
            return back()->withErrors(['member' => 'The project owner cannot be removed.']);
        }

        $teamIds = $project->teams()->pluck('id');

        DB::transaction(function () use ($project, $user, $teamIds, $request) {
            DB::table('team_members')
                ->whereIn('team_id', $teamIds)
                ->where('user_id', $user->id)
                ->delete();

            DB::table('project_members')
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->delete();

            ActivityLog::create([
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'action' => 'removed project member',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'old_values' => [
                    'member' => $user->email,
                ],
            ]);
        });

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Project member removed.');
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        abort_unless(
            $project->owner_id === $request->user()->id
                || $project->members()->where('users.id', $request->user()->id)->exists(),
            403
        );
    }

    private function authorizeProjectManagement(Request $request, Project $project): void
    {
        $memberRole = $project->members()
            ->where('users.id', $request->user()->id)
            ->first()
            ?->pivot
            ?->role;

        abort_unless(
            $project->owner_id === $request->user()->id
                || in_array($memberRole, ['project_owner', 'scrum_master'], true),
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
