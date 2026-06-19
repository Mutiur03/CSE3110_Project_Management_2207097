<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'projects' => $this->userProjects($request),
            'currentProject' => $this->currentProject($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $request->user()->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Password updated.');
    }

    private function userProjects(Request $request)
    {
        $user = $request->user();

        return Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->orderBy('name')
            ->get();
    }

    private function currentProject(Request $request): ?Project
    {
        $projects = $this->userProjects($request);

        return $projects->firstWhere('id', $request->query('project')) ?? $projects->first();
    }
}
