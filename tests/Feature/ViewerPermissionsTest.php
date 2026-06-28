<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Tests\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViewerPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_view_project_backlog(): void
    {
        [$project, $viewer] = $this->createProjectWithViewer();

        $response = $this->actingAs($viewer)->get(route('projects.issues.index', $project));

        $response->assertOk();
        $response->assertSee('Project backlog');
    }

    public function test_viewer_cannot_create_issue(): void
    {
        [$project, $viewer] = $this->createProjectWithViewer();

        $response = $this->actingAs($viewer)->post(route('projects.issues.store', $project), [
            'title' => 'Viewer created issue',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'story_points' => 2,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Viewer created issue',
        ]);
    }

    public function test_viewer_cannot_update_issue(): void
    {
        [$project, $viewer, $owner] = $this->createProjectWithViewerAndOwner();
        $issue = $this->createIssue($project, $owner);

        $response = $this->actingAs($viewer)->patch(route('projects.issues.update', [$project, $issue]), [
            'title' => 'Changed by viewer',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'story_points' => 2,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'title' => 'Build backlog workflow',
        ]);
    }

    public function test_viewer_cannot_comment_on_issue(): void
    {
        [$project, $viewer, $owner] = $this->createProjectWithViewerAndOwner();
        $issue = $this->createIssue($project, $owner);

        $response = $this->actingAs($viewer)->post(route('projects.issues.comments.store', [$project, $issue]), [
            'body' => 'Viewer comment',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('comments', [
            'issue_id' => $issue->id,
            'body' => 'Viewer comment',
        ]);
    }

    public function test_viewer_cannot_create_sprint(): void
    {
        [$project, $viewer] = $this->createProjectWithViewer();

        $response = $this->actingAs($viewer)->post(route('projects.sprints.store', $project), [
            'name' => 'Viewer sprint',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sprints', [
            'project_id' => $project->id,
            'name' => 'Viewer sprint',
        ]);
    }

    public function test_viewer_cannot_create_team(): void
    {
        [$project, $viewer] = $this->createProjectWithViewer();

        $response = $this->actingAs($viewer)->post(route('projects.teams.store', $project), [
            'name' => 'Viewer team',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teams', [
            'project_id' => $project->id,
            'name' => 'Viewer team',
        ]);
    }

    public function test_viewer_cannot_manage_project_members(): void
    {
        [$project, $viewer] = $this->createProjectWithViewer();
        $candidate = User::factory()->create();

        $response = $this->actingAs($viewer)->post(route('projects.members.store', $project), [
            'email' => $candidate->email,
            'role' => 'developer',
        ]);

        $response->assertForbidden();
    }

    public function test_developer_can_still_create_issue(): void
    {
        [$project, $developer] = $this->createProjectWithMember('developer');

        $response = $this->actingAs($developer)->post(route('projects.issues.store', $project), [
            'title' => 'Developer issue',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'story_points' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('issues', [
            'project_id' => $project->id,
            'title' => 'Developer issue',
        ]);
    }

    private function createProjectWithViewer(): array
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
        ]);

        $this->addProjectMember($project, $owner, 'project_owner');
        $this->addProjectMember($project, $viewer, 'viewer');

        return [$project, $viewer];
    }

    private function createProjectWithViewerAndOwner(): array
    {
        [$project, $viewer] = $this->createProjectWithViewer();
        $owner = User::find($project->owner_id);

        return [$project, $viewer, $owner];
    }

    private function createProjectWithMember(string $role): array
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Campus Portal',
            'key' => 'CP',
        ]);

        $this->addProjectMember($project, $owner, 'project_owner');
        $this->addProjectMember($project, $member, $role);

        return [$project, $member];
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

    private function createIssue(Project $project, User $reporter): Issue
    {
        return Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $reporter->id,
            'key' => 'CP-1',
            'title' => 'Build backlog workflow',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'story_points' => 2,
        ]);
    }
}
