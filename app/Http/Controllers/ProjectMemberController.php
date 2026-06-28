<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectMemberController extends Controller
{
    use AuthorizesProjectMembership;

    public function index(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);

        $members = collect(DB::select(
            'SELECT u.id, u.name, u.email, pm.role
             FROM users u
             INNER JOIN project_members pm ON pm.user_id = u.id
             WHERE pm.project_id = ?
             ORDER BY u.name',
            [$project],
        ));

        return view('projects.members.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'members' => $members,
        ]);
    }

    public function store(Request $request, string $project): RedirectResponse
    {
        $currentProject = $this->authorizeProjectManagement($request, $project);

        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(['project_owner', 'scrum_master', 'developer', 'viewer', 'admin'])],
        ], [
            'email.exists' => 'That email is not registered yet. Ask the user to create an account first, then add them to the project.',
        ]);

        $user = DB::selectOne('SELECT id, email FROM users WHERE email = ?', [$validated['email']]);
        abort_if($user === null, 404);

        if (DB::selectOne(
            'SELECT 1 AS found FROM project_members WHERE project_id = ? AND user_id = ?',
            [$project, $user->id],
        ) !== null) {
            return back()
                ->withErrors(['email' => 'This user is already a project member.'])
                ->withInput();
        }

        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO project_members (id, project_id, user_id, role, joined_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (string) Str::uuid(),
                $project,
                $user->id,
                $validated['role'],
                $now,
                $now,
                $now,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'added project member',
            'App\Models\User',
            $user->id,
            newValues: [
                'member' => $user->email,
                'role' => $validated['role'],
            ],
        );

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Project member added.');
    }

    public function update(Request $request, string $project, string $user): RedirectResponse
    {
        $this->authorizeProjectManagement($request, $project);

        abort_if(DB::selectOne(
            'SELECT 1 AS found FROM project_members WHERE project_id = ? AND user_id = ?',
            [$project, $user],
        ) === null, 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['project_owner', 'scrum_master', 'developer', 'viewer', 'admin'])],
        ]);

        $member = DB::selectOne('SELECT email FROM users WHERE id = ?', [$user]);

        DB::update(
            'UPDATE project_members SET role = ?, updated_at = ? WHERE project_id = ? AND user_id = ?',
            [$validated['role'], now()->toDateTimeString(), $project, $user],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'updated project member role',
            'App\Models\User',
            $user,
            newValues: [
                'member' => $member->email ?? $user,
                'role' => $validated['role'],
            ],
        );

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Project member updated.');
    }

    public function destroy(Request $request, string $project, string $user): RedirectResponse
    {
        $currentProject = $this->authorizeProjectManagement($request, $project);

        abort_if(DB::selectOne(
            'SELECT 1 AS found FROM project_members WHERE project_id = ? AND user_id = ?',
            [$project, $user],
        ) === null, 404);

        if ($currentProject->owner_id === $user) {
            return back()->withErrors(['member' => 'The project owner cannot be removed.']);
        }

        $member = DB::selectOne('SELECT email FROM users WHERE id = ?', [$user]);

        DB::transaction(function () use ($project, $user, $request, $member) {
            DB::delete(
                'DELETE FROM team_members
                 WHERE user_id = ?
                   AND team_id IN (SELECT id FROM teams WHERE project_id = ?)',
                [$user, $project],
            );

            DB::delete(
                'DELETE FROM project_members WHERE project_id = ? AND user_id = ?',
                [$project, $user],
            );

            $this->logActivity(
                $project,
                $request->user()->id,
                'removed project member',
                'App\Models\User',
                $user,
                oldValues: [
                    'member' => $member->email ?? $user,
                ],
            );
        });

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Project member removed.');
    }
}
