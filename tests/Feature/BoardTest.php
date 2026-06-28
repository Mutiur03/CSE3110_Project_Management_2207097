<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Tests\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_shows_message_when_no_active_sprint_exists(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->get(route('projects.board.index', $project));

        $response->assertOk();
        $response->assertSee('Start a sprint to use the board');
    }

    public function test_board_shows_active_sprint_issues(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);
        Issue::create([
            'project_id' => $project->id,
            'sprint_id' => $sprint->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Build board page',
            'type' => 'task',
            'status' => 'selected',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->get(route('projects.board.index', $project));

        $response->assertOk();
        $response->assertSee('Active sprint board');
        $response->assertSee('Build board page');
    }

    public function test_user_can_update_active_sprint_issue_status(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);
        $issue = Issue::create([
            'project_id' => $project->id,
            'sprint_id' => $sprint->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Build board page',
            'type' => 'task',
            'status' => 'selected',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->patch(route('projects.board.issues.status', [$project, $issue]), [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect(route('projects.board.index', $project));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'action' => 'changed issue status',
        ]);
    }

    public function test_backlog_issue_cannot_be_moved_from_board(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);
        $issue = Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Backlog issue',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->patch(route('projects.board.issues.status', [$project, $issue]), [
            'status' => 'in_progress',
        ]);

        $response->assertUnprocessable();
    }

    public function test_non_project_member_cannot_view_board(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('projects.board.index', $project));

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
}
