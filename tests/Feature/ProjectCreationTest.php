<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Tests\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_project_create_page(): void
    {
        $response = $this->get('/projects/create');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_project_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertOk();
        $response->assertSee('Create a project workspace');
    }

    public function test_authenticated_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'Student Portal',
            'description' => 'Student services workspace.',
        ]);

        $project = Project::where('key', 'SP')->firstOrFail();

        $response->assertRedirect(route('dashboard', ['project' => $project->id]));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'owner_id' => $user->id,
            'name' => 'Student Portal',
            'key' => 'SP',
        ]);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'project_owner',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'action' => 'created project',
        ]);
    }

    public function test_generated_project_key_stays_unique(): void
    {
        $user = User::factory()->create();
        Project::create([
            'owner_id' => $user->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
        ]);

        DB::table('project_members')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'project_id' => Project::where('key', 'CP')->value('id'),
            'user_id' => $user->id,
            'role' => 'project_owner',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'Campus Portal Copy',
        ]);

        $project = Project::where('name', 'Campus Portal Copy')->firstOrFail();

        $response->assertRedirect(route('dashboard', ['project' => $project->id]));
        $this->assertSame('CPC', $project->key);
    }

    public function test_generated_project_key_uses_suffix_for_duplicate_base(): void
    {
        $user = User::factory()->create();
        Project::create([
            'owner_id' => $user->id,
            'name' => 'Student Portal',
            'key' => 'SP',
        ]);

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'Sales Pipeline',
        ]);

        $project = Project::where('name', 'Sales Pipeline')->firstOrFail();

        $response->assertRedirect(route('dashboard', ['project' => $project->id]));
        $this->assertSame('SP2', $project->key);
    }
}
