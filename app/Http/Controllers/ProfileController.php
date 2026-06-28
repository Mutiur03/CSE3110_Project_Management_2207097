<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use AuthorizesProjectMembership;

    public function edit(Request $request): View
    {
        $projects = $this->userProjects($request);

        return view('profile.edit', [
            'user' => $request->user(),
            'projects' => $projects,
            'currentProject' => $projects->firstWhere('id', $request->query('project')) ?? $projects->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        DB::update(
            'UPDATE users SET name = ?, updated_at = ? WHERE id = ?',
            [$validated['name'], now()->toDateTimeString(), $request->user()->id],
        );

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

        DB::update(
            'UPDATE users SET password = ?, updated_at = ? WHERE id = ?',
            [Hash::make($validated['password']), now()->toDateTimeString(), $request->user()->id],
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Password updated.');
    }
}
