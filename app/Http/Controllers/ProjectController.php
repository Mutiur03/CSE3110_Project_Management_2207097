<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function create(Request $request): View
    {
        $projects = Project::query()
            ->where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->orderBy('name')
            ->get();

        return view('projects.create', [
            'projects' => $projects,
            'currentProject' => $projects->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $projectKey = $this->generateProjectKey($validated['name']);

        $project = DB::transaction(function () use ($request, $validated, $projectKey) {
            $project = Project::create([
                'owner_id' => $request->user()->id,
                'name' => $validated['name'],
                'key' => $projectKey,
                'description' => $validated['description'] ?? null,
                'status' => 'active',
            ]);

            DB::table('project_members')->insert([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'role' => 'project_owner',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ActivityLog::create([
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'action' => 'created project',
                'subject_type' => Project::class,
                'subject_id' => $project->id,
                'new_values' => [
                    'name' => $project->name,
                    'key' => $project->key,
                ],
            ]);

            return $project;
        });

        return redirect()
            ->route('dashboard', ['project' => $project->id])
            ->with('status', 'Project created.');
    }

    private function generateProjectKey(string $name): string
    {
        $words = collect(preg_split('/[^A-Za-z0-9]+/', $name))
            ->filter();

        $base = $words
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->join('');

        $base = Str::substr($base ?: 'PRJ', 0, 8);
        $key = $base;
        $counter = 2;

        while (Project::where('key', $key)->exists()) {
            $suffix = (string) $counter;
            $key = Str::substr($base, 0, 12 - strlen($suffix)) . $suffix;
            $counter++;
        }

        return $key;
    }
}
