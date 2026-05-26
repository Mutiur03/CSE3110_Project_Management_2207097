<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class IssueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_project_issues(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Create backlog page',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->get(route('projects.issues.index', $project));

        $response->assertOk();
        $response->assertSee('Project backlog');
        $response->assertSee('Create backlog page');
    }

    public function test_user_can_create_issue_without_team_or_assignee(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Add issue creation flow',
            'description' => 'Create project-scoped issue CRUD.',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'high',
        ]);

        $issue = Issue::where('key', 'CP-1')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertDatabaseHas('issues', [
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Add issue creation flow',
            'team_id' => null,
            'assignee_id' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'action' => 'created issue',
        ]);
    }

    public function test_user_can_create_issue_with_team_and_assignee(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Backend Team',
        ]);
        $this->addTeamMember($team, $member);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Build issue controller',
            'type' => 'task',
            'status' => 'selected',
            'priority' => 'medium',
            'assignee_id' => $member->id,
            'team_id' => $team->id,
            'story_points' => 3,
        ]);

        $issue = Issue::where('key', 'CP-1')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertSame($member->id, $issue->assignee_id);
        $this->assertSame($team->id, $issue->team_id);
    }

    public function test_user_cannot_assign_issue_to_member_outside_selected_team(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);
        $team = Team::create([
            'project_id' => $project->id,
            'name' => 'Backend Team',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Build issue controller',
            'type' => 'task',
            'status' => 'selected',
            'priority' => 'medium',
            'assignee_id' => $member->id,
            'team_id' => $team->id,
            'story_points' => 3,
        ]);

        $response->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Build issue controller',
        ]);
    }

    public function test_user_can_update_issue(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $issue = Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Old title',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->patch(route('projects.issues.update', [$project, $issue]), [
            'title' => 'Updated title',
            'type' => 'bug',
            'status' => 'review',
            'priority' => 'urgent',
        ]);

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'title' => 'Updated title',
            'type' => 'bug',
            'status' => 'review',
            'priority' => 'urgent',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'action' => 'updated issue',
        ]);
    }

    public function test_user_cannot_view_issues_for_project_they_do_not_belong_to(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('projects.issues.index', $project));

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
