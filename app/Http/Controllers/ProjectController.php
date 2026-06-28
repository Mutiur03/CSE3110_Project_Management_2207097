<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use AuthorizesProjectMembership;

    public function create(Request $request): View
    {
        $projects = $this->userProjects($request);

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

        $projectKey = $this->generateUniqueProjectKey($validated['name']);
        $now = now()->toDateTimeString();
        $projectId = (string) Str::uuid();
        $userId = $request->user()->id;

        DB::transaction(function () use ($validated, $projectKey, $now, $projectId, $userId) {
            DB::insert(
                'INSERT INTO projects (id, owner_id, name, key, description, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $projectId,
                    $userId,
                    $validated['name'],
                    $projectKey,
                    $validated['description'] ?? null,
                    'active',
                    $now,
                    $now,
                ],
            );

            DB::insert(
                'INSERT INTO project_members (id, project_id, user_id, role, joined_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    (string) Str::uuid(),
                    $projectId,
                    $userId,
                    'project_owner',
                    $now,
                    $now,
                    $now,
                ],
            );

            $this->logActivity(
                $projectId,
                $userId,
                'created project',
                'App\Models\Project',
                $projectId,
                newValues: [
                    'name' => $validated['name'],
                    'key' => $projectKey,
                ],
            );
        });

        return redirect()
            ->route('dashboard', ['project' => $projectId])
            ->with('status', 'Project created.');
    }

    public function edit(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectManagement($request, $project);

        return view('projects.settings.edit', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
        ]);
    }

    public function update(Request $request, string $project): RedirectResponse
    {
        $currentProject = $this->authorizeProjectManagement($request, $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = [
            'name' => $currentProject->name,
            'description' => $currentProject->description,
            'status' => $currentProject->status,
        ];

        $newValues = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ];

        DB::update(
            'UPDATE projects SET name = ?, description = ?, status = ?, updated_at = ? WHERE id = ?',
            [
                $newValues['name'],
                $newValues['description'],
                $newValues['status'],
                now()->toDateTimeString(),
                $project,
            ],
        );

        $this->logActivity(
            $project,
            $request->user()->id,
            'updated project',
            'App\Models\Project',
            $project,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return redirect()
            ->route('projects.settings.edit', $project)
            ->with('status', 'Project settings saved.');
    }

    private function generateUniqueProjectKey(string $name): string
    {
        $words = collect(preg_split('/[^A-Za-z0-9]+/', $name))->filter();

        $base = $words
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->join('');

        $base = Str::substr($base ?: 'PRJ', 0, 8);
        $key = $base;
        $counter = 2;

        while (DB::selectOne('SELECT 1 AS found FROM projects WHERE key = ?', [$key]) !== null) {
            $suffix = (string) $counter;
            $key = Str::substr($base, 0, 12 - strlen($suffix)) . $suffix;
            $counter++;
        }

        return $key;
    }
}
