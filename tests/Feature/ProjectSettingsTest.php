<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Tests\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_view_settings_page(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->get(route('projects.settings.edit', $project));

        $response->assertOk();
        $response->assertSee('Project settings');
        $response->assertSee($project->key);
    }

    public function test_developer_cannot_view_settings_page(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $developer = User::factory()->create();
        $this->addProjectMember($project, $developer, 'developer');

        $response = $this->actingAs($developer)->get(route('projects.settings.edit', $project));

        $response->assertForbidden();
    }

    public function test_project_owner_can_update_project_settings(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->patch(route('projects.update', $project), [
            'name' => 'Updated Portal',
            'description' => 'Updated description.',
            'status' => 'archived',
        ]);

        $response->assertRedirect(route('projects.settings.edit', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Portal',
            'description' => 'Updated description.',
            'status' => 'archived',
            'key' => $project->key,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'updated project',
        ]);
    }

    public function test_archived_project_blocks_issue_creation_for_developer(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $developer = User::factory()->create();
        $this->addProjectMember($project, $developer, 'developer');

        $project->update(['status' => 'archived']);

        $response = $this->actingAs($developer)->post(route('projects.issues.store', $project), [
            'title' => 'Should not create',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'story_points' => 2,
        ]);

        $response->assertForbidden();
    }

    public function test_scrum_master_can_reactivate_archived_project(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $scrumMaster = User::factory()->create();
        $this->addProjectMember($project, $scrumMaster, 'scrum_master');
        $project->update(['status' => 'archived']);

        $response = $this->actingAs($scrumMaster)->patch(route('projects.update', $project), [
            'name' => $project->name,
            'description' => $project->description,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('projects.settings.edit', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'active',
        ]);
    }

    private function createProjectWithOwner(): array
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
            'description' => 'Student services workspace.',
            'status' => 'active',
        ]);

        $this->addProjectMember($project, $owner, 'project_owner');

        return [$owner, $project];
    }

    private function addProjectMember(Project $project, User $user, string $role = 'developer'): void
    {
        DB::table('project_members')->insert([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
