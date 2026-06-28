<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Tests\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_project_teams(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        Team::create([
            'project_id' => $project->id,
            'name' => 'Frontend Team',
            'description' => 'Blade views and dashboard UI.',
        ]);

        $response = $this->actingAs($owner)->get(route('projects.teams.index', $project));

        $response->assertOk();
        $response->assertSee('Project teams');
        $response->assertSee('Frontend Team');
    }

    public function test_user_can_create_team_for_project(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.teams.store', $project), [
            'name' => 'Backend Team',
            'description' => 'Laravel controllers and database logic.',
        ]);

        $response->assertRedirect(route('projects.teams.index', $project));
        $this->assertDatabaseHas('teams', [
            'project_id' => $project->id,
            'name' => 'Backend Team',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'created team',
        ]);
    }

    public function test_team_name_must_be_unique_inside_project(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        Team::create([
            'project_id' => $project->id,
            'name' => 'QA Team',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.teams.store', $project), [
            'name' => 'QA Team',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_add_project_member_to_team(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Frontend Team',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.teams.members.store', [$project, $team]), [
            'user_id' => $member->id,
            'role' => 'developer',
        ]);

        $response->assertRedirect(route('projects.teams.index', $project));
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'developer',
        ]);
    }

    public function test_user_cannot_manage_project_they_do_not_belong_to(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('projects.teams.index', $project));

        $response->assertForbidden();
    }

    public function test_user_can_remove_member_from_team(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Frontend Team',
        ]);
        $this->addTeamMember($team, $member);

        $response = $this->actingAs($owner)->delete(route('projects.teams.members.destroy', [$project, $team, $member]));

        $response->assertRedirect(route('projects.teams.index', $project));
        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'removed team member',
        ]);
    }

    public function test_user_can_delete_team_and_unassign_linked_issues(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Backend Team',
        ]);
        $issueId = (string) Str::uuid();
        DB::table('issues')->insert([
            'id' => $issueId,
            'project_id' => $project->id,
            'team_id' => $team->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Team scoped task',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($owner)->delete(route('projects.teams.destroy', [$project, $team]));

        $response->assertRedirect(route('projects.teams.index', $project));
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseHas('issues', [
            'id' => $issueId,
            'team_id' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'deleted team',
        ]);
    }

    public function test_viewer_cannot_remove_team_member(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $viewer = User::factory()->create();
        $member = User::factory()->create();
        $this->addProjectMember($project, $viewer, 'viewer');
        $this->addProjectMember($project, $member);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'QA Team',
        ]);
        $this->addTeamMember($team, $member);

        $response = $this->actingAs($viewer)->delete(route('projects.teams.members.destroy', [$project, $team, $member]));

        $response->assertForbidden();
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
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

    private function addTeamMember(Team $team, User $user, string $role = 'developer'): void
    {
        DB::table('team_members')->insert([
            'id' => (string) Str::uuid(),
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
