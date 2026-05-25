<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $members = User::factory(5)->create();

        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
            'description' => 'Student services, authentication, notices, and result workflows.',
            'status' => 'active',
        ]);

        $projectUsers = collect([$owner])->concat($members);

        $projectUsers->each(function (User $user) use ($project, $owner) {
            DB::table('project_members')->insert([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => $user->is($owner) ? 'project_owner' : 'developer',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $teams = collect([
            ['name' => 'Frontend Team', 'description' => 'Dashboard, forms, responsive views, and Blade components.'],
            ['name' => 'Backend Team', 'description' => 'Authentication, project data, issue workflow, and validation.'],
            ['name' => 'QA Team', 'description' => 'Bug checks, sprint acceptance, and regression testing.'],
            ['name' => 'Documentation Team', 'description' => 'Reports, ER diagrams, schema notes, and Scrum learning material.'],
        ])->map(fn (array $team) => Team::create([
            'project_id' => $project->id,
            'name' => $team['name'],
            'description' => $team['description'],
        ]));

        $teams->each(function (Team $team, int $index) use ($members, $owner) {
            collect([$owner, $members[$index], $members[($index + 1) % $members->count()]])
                ->unique('id')
                ->each(function (User $user) use ($team, $owner) {
                    DB::table('team_members')->insert([
                        'id' => (string) Str::uuid(),
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => $user->is($owner) ? 'scrum_master' : 'developer',
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        });

        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'goal' => 'Complete the project-scoped dashboard and authentication foundation.',
            'start_date' => now()->startOfWeek(),
            'end_date' => now()->startOfWeek()->addDays(11),
            'status' => 'active',
        ]);

        $issues = collect([
            ['key' => 'CP-1', 'title' => 'Create project workspace dashboard', 'type' => 'story', 'status' => 'done', 'team' => 0, 'points' => 5],
            ['key' => 'CP-2', 'title' => 'Build reusable dashboard layout components', 'type' => 'task', 'status' => 'done', 'team' => 0, 'points' => 3],
            ['key' => 'CP-3', 'title' => 'Store project teams in the database', 'type' => 'story', 'status' => 'in_progress', 'team' => 1, 'points' => 5],
            ['key' => 'CP-4', 'title' => 'Add active sprint board columns', 'type' => 'task', 'status' => 'review', 'team' => 0, 'points' => 3],
            ['key' => 'CP-5', 'title' => 'Validate login and registration flow', 'type' => 'bug', 'status' => 'review', 'team' => 2, 'points' => 2],
            ['key' => 'CP-6', 'title' => 'Write project report and schema notes', 'type' => 'task', 'status' => 'selected', 'team' => 3, 'points' => 2],
            ['key' => 'CP-7', 'title' => 'Define issue hierarchy for epics and stories', 'type' => 'epic', 'status' => 'backlog', 'team' => 1, 'points' => null],
        ])->map(fn (array $issue) => Issue::create([
            'project_id' => $project->id,
            'team_id' => $teams[$issue['team']]->id,
            'sprint_id' => $issue['status'] === 'backlog' ? null : $sprint->id,
            'reporter_id' => $owner->id,
            'assignee_id' => $members[$issue['team']]->id,
            'key' => $issue['key'],
            'title' => $issue['title'],
            'type' => $issue['type'],
            'status' => $issue['status'],
            'priority' => $issue['type'] === 'bug' ? 'high' : 'medium',
            'story_points' => $issue['points'],
        ]));

        Comment::create([
            'issue_id' => $issues[2]->id,
            'user_id' => $owner->id,
            'body' => 'Keep this tied to the selected project so switching workspaces loads the right teams and issues.',
        ]);

        foreach ($issues->take(5) as $issue) {
            ActivityLog::create([
                'project_id' => $project->id,
                'issue_id' => $issue->id,
                'user_id' => $issue->assignee_id,
                'action' => 'updated',
                'subject_type' => Issue::class,
                'subject_id' => $issue->id,
                'new_values' => ['status' => $issue->status],
            ]);
        }
    }
}
