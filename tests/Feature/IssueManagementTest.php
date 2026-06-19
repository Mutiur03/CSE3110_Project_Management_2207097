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

    public function test_backlog_page_shows_folding_attributes_for_issues_with_children(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $epic = Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-1',
            'title' => 'Core Epic',
            'type' => 'epic',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $story = Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-2',
            'title' => 'Child Story',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $epic->id,
            'story_points' => 3,
        ]);
        $subtask = Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-3',
            'title' => 'Child Subtask',
            'type' => 'subtask',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $story->id,
        ]);

        $response = $this->actingAs($owner)->get(route('projects.issues.index', $project));

        $response->assertOk();
        $response->assertSee('data-backlog-row');
        $response->assertSeeHtml('data-toggle-for="' . $epic->id . '"');
        $response->assertSeeHtml('data-toggle-for="' . $story->id . '"');
        $response->assertSeeHtml('data-parent-id="' . $epic->id . '"');
        $response->assertSeeHtml('data-parent-id="' . $story->id . '"');
    }

    public function test_user_can_create_issue_without_team_or_assignee(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Add issue creation flow',
            'description' => 'Create project-scoped issue CRUD.',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'high',
            'story_points' => 3,
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
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_issue_assignment_notification_is_stored_for_assignee(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create();
        $this->addProjectMember($project, $member);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Notify assigned developer',
            'type' => 'task',
            'status' => 'selected',
            'priority' => 'medium',
            'assignee_id' => $member->id,
            'story_points' => 3,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(1, $member->fresh()->unreadNotifications()->count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_issue_assignment_notifies_actor_when_assigning_to_self(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Self assigned task',
            'type' => 'task',
            'status' => 'selected',
            'priority' => 'medium',
            'assignee_id' => $owner->id,
            'story_points' => 3,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_story_can_be_created_under_epic(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $epic = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'User management',
            'type' => 'epic',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Register account',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $epic->id,
            'story_points' => 5,
        ]);

        $issue = Issue::where('key', 'CP-2')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertSame($epic->id, $issue->parent_issue_id);
        $this->assertSame(5, $issue->story_points);
    }

    public function test_story_requires_parent_and_points(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Register account',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors(['parent_issue_id', 'story_points']);
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Register account',
        ]);
    }

    public function test_subtask_can_be_created_under_story(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $story = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Register account',
            'type' => 'story',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Build registration form',
            'type' => 'subtask',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $story->id,
            'story_points' => 3,
        ]);

        $issue = Issue::where('key', 'CP-2')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertSame($story->id, $issue->parent_issue_id);
        $this->assertNull($issue->story_points);
    }

    public function test_issue_detail_shows_child_issues(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $epic = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Authentication',
            'type' => 'epic',
        ]);
        $story = $this->createIssue($project, $owner, [
            'key' => 'CP-2',
            'title' => 'User can log in',
            'type' => 'story',
            'parent_issue_id' => $epic->id,
            'story_points' => 5,
        ]);
        $subtask = $this->createIssue($project, $owner, [
            'key' => 'CP-3',
            'title' => 'Build login form',
            'type' => 'subtask',
            'parent_issue_id' => $story->id,
        ]);

        $response = $this->actingAs($owner)->get(route('projects.issues.show', [$project, $epic]));

        $response->assertOk();
        $response->assertSee('Child issues');
        $response->assertSee($story->title);
        $response->assertSee($subtask->title);
    }

    public function test_subtask_requires_parent(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Build registration form',
            'type' => 'subtask',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('parent_issue_id');
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Build registration form',
        ]);
    }

    public function test_task_clears_parent_issue(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $story = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Register account',
            'type' => 'story',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Configure mail service',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $story->id,
            'story_points' => 3,
        ]);

        $issue = Issue::where('key', 'CP-2')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertNull($issue->parent_issue_id);
        $this->assertSame(3, $issue->story_points);
    }

    public function test_task_requires_points(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Configure mail service',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('story_points');
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Configure mail service',
        ]);
    }

    public function test_subtask_cannot_be_created_under_epic(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $epic = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Authentication',
            'type' => 'epic',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Build registration form',
            'type' => 'subtask',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $epic->id,
        ]);

        $response->assertSessionHasErrors('parent_issue_id');
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Build registration form',
        ]);
    }

    public function test_story_cannot_be_created_under_story(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $story = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Register account',
            'type' => 'story',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Nested story',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $story->id,
            'story_points' => 3,
        ]);

        $response->assertSessionHasErrors('parent_issue_id');
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Nested story',
        ]);
    }

    public function test_bug_clears_parent_and_story_points(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $epic = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'User management',
            'type' => 'epic',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Fix password reset error',
            'type' => 'bug',
            'status' => 'backlog',
            'priority' => 'high',
            'parent_issue_id' => $epic->id,
            'story_points' => 8,
            'severity' => 'major',
            'environment' => 'Local browser',
            'steps_to_reproduce' => 'Open the reset form.',
            'expected_result' => 'The reset form works.',
            'actual_result' => 'The reset form fails.',
        ]);

        $issue = Issue::where('key', 'CP-2')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertNull($issue->parent_issue_id);
        $this->assertNull($issue->story_points);
    }

    public function test_user_can_create_bug_with_bug_report_details(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Password reset crashes',
            'description' => 'Reset page fails for valid users.',
            'type' => 'bug',
            'status' => 'backlog',
            'priority' => 'urgent',
            'severity' => 'critical',
            'environment' => 'Chrome on Windows staging',
            'steps_to_reproduce' => "Open login\nClick forgot password\nSubmit valid email",
            'expected_result' => 'Password reset email is sent.',
            'actual_result' => 'A server error appears.',
        ]);

        $issue = Issue::where('key', 'CP-1')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'type' => 'bug',
            'severity' => 'critical',
            'environment' => 'Chrome on Windows staging',
            'expected_result' => 'Password reset email is sent.',
            'actual_result' => 'A server error appears.',
        ]);
    }

    public function test_bug_requires_bug_report_details(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Password reset crashes',
            'type' => 'bug',
            'status' => 'backlog',
            'priority' => 'urgent',
        ]);

        $response->assertSessionHasErrors([
            'severity',
            'environment',
            'steps_to_reproduce',
            'expected_result',
            'actual_result',
        ]);
        $this->assertDatabaseMissing('issues', [
            'project_id' => $project->id,
            'title' => 'Password reset crashes',
        ]);
    }

    public function test_non_bug_issue_clears_bug_report_details(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();

        $response = $this->actingAs($owner)->post(route('projects.issues.store', $project), [
            'title' => 'Build registration form',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'story_points' => 3,
            'severity' => 'critical',
            'environment' => 'Chrome on Windows staging',
            'steps_to_reproduce' => 'Open the form.',
            'expected_result' => 'The form works.',
            'actual_result' => 'The form fails.',
        ]);

        $issue = Issue::where('key', 'CP-1')->firstOrFail();

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertNull($issue->severity);
        $this->assertNull($issue->environment);
        $this->assertNull($issue->steps_to_reproduce);
        $this->assertNull($issue->expected_result);
        $this->assertNull($issue->actual_result);
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
            'severity' => 'critical',
            'environment' => 'Chrome on Windows staging',
            'steps_to_reproduce' => 'Open the page.',
            'expected_result' => 'The page loads.',
            'actual_result' => 'The page crashes.',
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

    public function test_user_can_filter_project_issues(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $member = User::factory()->create(['name' => 'Assigned Developer']);
        $this->addProjectMember($project, $member);
        $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Fix login failure',
            'type' => 'bug',
            'status' => 'review',
            'priority' => 'urgent',
            'assignee_id' => $member->id,
            'severity' => 'critical',
        ]);
        $this->createIssue($project, $owner, [
            'key' => 'CP-2',
            'title' => 'Build profile page',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->get(route('projects.issues.index', [
            'project' => $project,
            'q' => 'login',
            'type' => 'bug',
            'status' => 'review',
            'priority' => 'urgent',
            'assignee_id' => $member->id,
        ]));

        $response->assertOk();
        $response->assertSee('Fix login failure');
        $response->assertSee('value="bug" selected', false);
        $response->assertSee('value="review" selected', false);
    }

    public function test_user_can_comment_on_issue_and_see_activity(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $issue = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Build comment thread',
        ]);

        $response = $this->actingAs($owner)->post(route('projects.issues.comments.store', [$project, $issue]), [
            'body' => 'Please test this before moving to Done.',
        ]);

        $response->assertRedirect(route('projects.issues.show', [$project, $issue]));
        $this->assertDatabaseHas('comments', [
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'body' => 'Please test this before moving to Done.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'issue_id' => $issue->id,
            'action' => 'commented on issue',
        ]);

        $this->actingAs($owner)
            ->get(route('projects.issues.show', [$project, $issue]))
            ->assertOk()
            ->assertSee('Comments')
            ->assertSee('Please test this before moving to Done.')
            ->assertSee('Issue activity')
            ->assertSee('commented on issue');
    }

    public function test_project_activity_page_shows_recent_changes(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $issue = $this->createIssue($project, $owner, [
            'key' => 'CP-1',
            'title' => 'Track project history',
        ]);

        $this->actingAs($owner)->post(route('projects.issues.comments.store', [$project, $issue]), [
            'body' => 'This should appear in project activity.',
        ]);

        $response = $this->actingAs($owner)->get(route('projects.activity.index', $project));

        $response->assertOk();
        $response->assertSee('Activity timeline');
        $response->assertSee('commented on issue');
        $response->assertSee('CP-1');
    }

    public function test_user_cannot_view_issues_for_project_they_do_not_belong_to(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('projects.issues.index', $project));

        $response->assertForbidden();
    }

    public function test_user_can_delete_issue_without_children(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $issue = $this->createIssue($project, $owner, [
            'key' => 'CP-9',
            'title' => 'Remove onboarding flow',
        ]);

        $response = $this->actingAs($owner)->delete(route('projects.issues.destroy', [$project, $issue]));

        $response->assertRedirect(route('projects.issues.index', $project));
        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'action' => 'deleted issue',
        ]);
    }

    public function test_user_cannot_delete_issue_with_children(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $epic = Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-10',
            'title' => 'Parent Epic',
            'type' => 'epic',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
            'key' => 'CP-11',
            'title' => 'Child Story',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'parent_issue_id' => $epic->id,
            'story_points' => 3,
        ]);

        $response = $this->actingAs($owner)->from(route('projects.issues.show', [$project, $epic]))
            ->delete(route('projects.issues.destroy', [$project, $epic]));

        $response->assertRedirect(route('projects.issues.show', [$project, $epic]));
        $response->assertSessionHasErrors('issue');
        $this->assertDatabaseHas('issues', ['id' => $epic->id]);
    }

    public function test_viewer_cannot_delete_issue(): void
    {
        [$owner, $project] = $this->createProjectWithOwner();
        $viewer = User::factory()->create();
        $this->addProjectMember($project, $viewer, 'viewer');
        $issue = $this->createIssue($project, $owner);

        $response = $this->actingAs($viewer)->delete(route('projects.issues.destroy', [$project, $issue]));

        $response->assertForbidden();
        $this->assertDatabaseHas('issues', ['id' => $issue->id]);
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

    private function createIssue(Project $project, User $reporter, array $overrides = []): Issue
    {
        return Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $reporter->id,
            'key' => 'CP-1',
            'title' => 'Build backlog workflow',
            'type' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            ...$overrides,
        ]);
    }
}
