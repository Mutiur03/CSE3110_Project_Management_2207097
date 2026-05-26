<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_member_page_can_be_viewed(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->get(route('projects.members.index', $project));

        $response->assertOk();
        $response->assertSee('Project members');
        $response->assertSee($owner->email);
    }

    public function test_project_owner_can_add_member_by_email(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create(['email' => 'member@example.com']);

        $response = $this->actingAs($owner)->post(route('projects.members.store', $project), [
            'email' => 'member@example.com',
            'role' => 'developer',
        ]);

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'developer',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'added project member',
        ]);
    }

    public function test_project_member_role_can_be_updated(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);

        $response = $this->actingAs($owner)->patch(route('projects.members.update', [$project, $member]), [
            'role' => 'scrum_master',
        ]);

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'scrum_master',
        ]);
    }

    public function test_project_member_can_be_removed_and_removed_from_project_teams(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Frontend Team',
        ]);
        DB::table('team_members')->insert([
            'id' => (string) Str::uuid(),
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'developer',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $member]));

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_project_owner_cannot_be_removed(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $owner]));

        $response->assertSessionHasErrors('member');
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_non_project_member_cannot_view_members(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('projects.members.index', $project));

        $response->assertForbidden();
    }

    private function createProjectWithOwner(): array
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
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
