<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SprintManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_project_sprints(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($owner)->get(route('projects.sprints.index', $project));

        $response->assertOk();
        $response->assertSee('Sprint planning');
        $response->assertSee('Sprint 1');
    }

    public function test_user_can_create_sprint(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.sprints.store', $project), [
            'name' => 'Sprint 1',
            'goal' => 'Build backlog workflow.',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-14',
        ]);

        $response->assertRedirect(route('projects.sprints.index', $project));
        $this->assertDatabaseHas('sprints', [
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'planned',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'created sprint',
        ]);
    }

    public function test_user_can_update_sprint_details(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'goal' => 'Old goal',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($owner)->patch(route('projects.sprints.update', [$project, $sprint]), [
            'name' => 'Sprint 1 Updated',
            'goal' => 'Updated goal',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-14',
        ]);

        $response->assertRedirect(route('projects.sprints.index', $project));
        $this->assertDatabaseHas('sprints', [
            'id' => $sprint->id,
            'name' => 'Sprint 1 Updated',
            'goal' => 'Updated goal',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'updated sprint',
        ]);
    }

    public function test_user_can_add_backlog_issue_to_sprint(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'planned',
        ]);
        $issue = $this->createIssue($project, $owner);

        $response = $this->actingAs($owner)->post(route('projects.sprints.issues.store', [$project, $sprint]), [
            'issue_id' => $issue->id,
        ]);

        $response->assertRedirect(route('projects.sprints.index', $project));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'sprint_id' => $sprint->id,
            'status' => 'selected',
        ]);
    }

    public function test_user_can_remove_issue_from_sprint(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'planned',
        ]);
        $issue = $this->createIssue($project, $owner, [
            'sprint_id' => $sprint->id,
            'status' => 'selected',
        ]);

        $response = $this->actingAs($owner)->delete(route('projects.sprints.issues.destroy', [$project, $sprint, $issue]));

        $response->assertRedirect(route('projects.sprints.index', $project));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'sprint_id' => null,
            'status' => 'backlog',
        ]);
    }

    public function test_user_can_start_sprint_and_only_one_sprint_is_active(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $activeSprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);
        $plannedSprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 2',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.sprints.start', [$project, $plannedSprint]));

        $response->assertRedirect(route('projects.sprints.index', $project));
        $this->assertDatabaseHas('sprints', [
            'id' => $plannedSprint->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('sprints', [
            'id' => $activeSprint->id,
            'status' => 'planned',
        ]);
    }

    public function test_user_can_complete_sprint_and_return_unfinished_issues_to_backlog(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);
        $unfinished = $this->createIssue($project, $owner, [
            'sprint_id' => $sprint->id,
            'status' => 'in_progress',
            'key' => 'CP-1',
        ]);
        $done = $this->createIssue($project, $owner, [
            'sprint_id' => $sprint->id,
            'status' => 'done',
            'key' => 'CP-2',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.sprints.complete', [$project, $sprint]));

        $response->assertRedirect(route('projects.sprints.index', $project));
        $this->assertDatabaseHas('sprints', [
            'id' => $sprint->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('issues', [
            'id' => $unfinished->id,
            'sprint_id' => null,
            'status' => 'backlog',
        ]);
        $this->assertDatabaseHas('issues', [
            'id' => $done->id,
            'sprint_id' => $sprint->id,
            'status' => 'done',
        ]);
    }

    public function test_non_project_member_cannot_view_sprints(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('projects.sprints.index', $project));

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

        DB::table('project_members')->insert([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => 'project_owner',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$owner, $project];
    }

    private function createIssue(Project $project, User $reporter, array $overrides = []): Issue
    {
        return Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $reporter->id,
            'key' => 'CP-1',
            'title' => 'Build sprint planning',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            ...$overrides,
        ]);
    }
}
