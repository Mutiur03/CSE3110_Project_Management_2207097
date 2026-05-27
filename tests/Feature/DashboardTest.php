<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_without_project_sees_create_project_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Create your first Scrum workspace');
    }

    public function test_dashboard_prevents_browser_back_cache_after_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
    }

    public function test_authenticated_user_can_view_project_dashboard_data(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
            'description' => 'Student services portal.',
        ]);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Frontend Team',
            'description' => 'Dashboard and forms.',
        ]);
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);
        Issue::create([
            'project_id' => $project->id,
            'team_id' => $team->id,
            'sprint_id' => $sprint->id,
            'reporter_id' => $user->id,
            'key' => 'CP-1',
            'title' => 'Build project dashboard',
            'type' => 'task',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Campus Portal');
        $response->assertSee('Teams in this project');
        $response->assertSee('Active sprint');
        $response->assertSee('Build project dashboard');
        $response->assertSee('Create backlog item');
        $response->assertSee('Epic');
        $response->assertSee('Story');
        $response->assertSee('Task');
        $response->assertSee('Subtask');
        $response->assertSee('Bug');
    }
}
