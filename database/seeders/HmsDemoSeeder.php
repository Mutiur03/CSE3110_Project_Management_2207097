<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/*
 * Demo data for the existing "Hall Management System" (HMS) project.
 *
 * Fills the workspace the owner already created — members, teams, three
 * sprints (completed / active / planned) and a full issue hierarchy of
 * epics -> stories/tasks -> subtasks + bugs — so every dashboard card and
 * report renders with realistic, domain-relevant content.
 *
 * Run: php artisan db:seed --class=Database\\Seeders\\HmsDemoSeeder
 */
class HmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('key', 'HMS')->first();

        if (! $project) {
            $this->command?->error('No project with key "HMS" found. Nothing seeded.');

            return;
        }

        if ($project->teams()->exists()) {
            $this->command?->warn('HMS project already has teams — skipping to avoid duplicates.');

            return;
        }

        $owner = User::find($project->owner_id);
        $now = Carbon::parse('2026-07-18');

        // --- People -----------------------------------------------------
        $people = collect([
            ['name' => 'Ayesha Siddiqua', 'email' => 'ayesha@hms.test', 'job_title' => 'Frontend Developer'],
            ['name' => 'Tanvir Ahmed', 'email' => 'tanvir@hms.test', 'job_title' => 'Backend Developer'],
            ['name' => 'Sabbir Hossain', 'email' => 'sabbir@hms.test', 'job_title' => 'Backend Developer'],
            ['name' => 'Nusrat Jahan', 'email' => 'nusrat@hms.test', 'job_title' => 'QA Engineer'],
            ['name' => 'Farhana Akter', 'email' => 'farhana@hms.test', 'job_title' => 'UI / Docs'],
            ['name' => 'Imran Kabir', 'email' => 'imran@hms.test', 'job_title' => 'Hall Supervisor'],
        ])->mapWithKeys(fn (array $p) => [
            Str::before($p['email'], '@') => User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'password' => Hash::make('password'),
                    'job_title' => $p['job_title'],
                    'email_verified_at' => $now,
                ],
            ),
        ]);

        $ayesha = $people['ayesha'];
        $tanvir = $people['tanvir'];
        $sabbir = $people['sabbir'];
        $nusrat = $people['nusrat'];
        $farhana = $people['farhana'];
        $imran = $people['imran'];

        // --- Project membership (Imran left team-less on purpose) --------
        $membership = [
            [$owner, 'project_owner'],
            [$tanvir, 'developer'],
            [$sabbir, 'developer'],
            [$ayesha, 'developer'],
            [$farhana, 'developer'],
            [$nusrat, 'developer'],
            [$imran, 'viewer'],
        ];

        foreach ($membership as [$user, $role]) {
            $exists = DB::table('project_members')
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('project_members')->insert([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => $role,
                'joined_at' => $now->copy()->subDays(40),
                'created_at' => $now->copy()->subDays(40),
                'updated_at' => $now->copy()->subDays(40),
            ]);
        }

        // --- Teams ------------------------------------------------------
        $teamDefs = [
            'frontend' => ['Frontend Team', 'Resident portal, room booking forms and dashboard widgets.', [[$owner, 'scrum_master'], [$ayesha, 'developer'], [$farhana, 'developer']]],
            'backend' => ['Backend Team', 'Allocation logic, fee/billing engine, auth and validation.', [[$owner, 'scrum_master'], [$tanvir, 'developer'], [$sabbir, 'developer']]],
            'qa' => ['QA Team', 'Bug verification, sprint acceptance and regression testing.', [[$owner, 'scrum_master'], [$nusrat, 'developer']]],
            'docs' => ['Documentation Team', 'Reports, ER diagrams, schema notes and user guides.', [[$owner, 'scrum_master'], [$farhana, 'developer']]],
        ];

        $teams = [];
        foreach ($teamDefs as $slug => [$name, $desc, $members]) {
            $team = Team::create([
                'project_id' => $project->id,
                'name' => $name,
                'description' => $desc,
            ]);
            $teams[$slug] = $team;

            foreach ($members as [$user, $role]) {
                DB::table('team_members')->insert([
                    'id' => (string) Str::uuid(),
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'joined_at' => $now->copy()->subDays(38),
                    'created_at' => $now->copy()->subDays(38),
                    'updated_at' => $now->copy()->subDays(38),
                ]);
            }
        }

        // --- Sprints ----------------------------------------------------
        $sprint1 = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1 — Foundation',
            'goal' => 'Resident registration, hall/room data model and secure role-based access.',
            'start_date' => $now->copy()->subDays(32),
            'end_date' => $now->copy()->subDays(19),
            'status' => 'completed',
        ]);

        $sprint2 = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 2 — Allocation & Billing',
            'goal' => 'Room allocation workflow, monthly fee invoices and resident dues dashboard.',
            'start_date' => $now->copy()->startOfWeek(),
            'end_date' => $now->copy()->startOfWeek()->addDays(13),
            'status' => 'active',
        ]);

        $sprint3 = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 3 — Notices & Reporting',
            'goal' => 'Complaint tracking, notice board publishing and occupancy reports.',
            'start_date' => $now->copy()->startOfWeek()->addDays(14),
            'end_date' => $now->copy()->startOfWeek()->addDays(27),
            'status' => 'planned',
        ]);

        // --- Epics ------------------------------------------------------
        $epicOnboard = $this->issue($project, $teams['backend'], null, $owner, $sabbir, 'HMS-1', 'Resident onboarding & room allocation', 'epic', 'in_progress', 'high', null, $now->copy()->subDays(34), $now->copy()->subDays(2),
            'Everything from a student registering to being allocated a room and later vacating.');

        $epicBilling = $this->issue($project, $teams['backend'], null, $owner, $tanvir, 'HMS-2', 'Fees & billing management', 'epic', 'in_progress', 'high', null, $now->copy()->subDays(33), $now->copy()->subDays(3),
            'Monthly hall fee invoicing, payment recording and dues tracking per resident.');

        $epicNotices = $this->issue($project, $teams['frontend'], null, $owner, $farhana, 'HMS-3', 'Notices & complaints', 'epic', 'backlog', 'medium', null, $now->copy()->subDays(30), $now->copy()->subDays(30),
            'Notice board publishing and a resident complaint submission / tracking flow.');

        // --- Sprint 1 (completed, all done) -----------------------------
        $this->issue($project, $teams['frontend'], $sprint1, $owner, $ayesha, 'HMS-4', 'Resident registration form', 'story', 'done', 'high', 5, $now->copy()->subDays(31), $now->copy()->subDays(27), 'Capture student ID, department, guardian and contact details.', $epicOnboard->id);
        $this->issue($project, $teams['backend'], $sprint1, $owner, $tanvir, 'HMS-5', 'Import halls, blocks and rooms', 'task', 'done', 'medium', 3, $now->copy()->subDays(31), $now->copy()->subDays(28), 'Seed the hall > block > floor > room hierarchy with capacities.', $epicOnboard->id);
        $this->issue($project, $teams['backend'], $sprint1, $owner, $sabbir, 'HMS-6', 'Allocate rooms to residents', 'story', 'done', 'high', 8, $now->copy()->subDays(30), $now->copy()->subDays(21), 'Assign an available bed to an approved resident and mark occupancy.', $epicOnboard->id);
        $this->issue($project, $teams['qa'], $sprint1, $owner, $nusrat, 'HMS-7', 'Duplicate room assignment allowed', 'bug', 'done', 'high', 2, $now->copy()->subDays(29), $now->copy()->subDays(26),
            'Two residents could be allocated the same bed.', null, 'major', 'Allocate resident A to Room 204-B, then allocate resident B to the same bed.', 'Second allocation is rejected.', 'Second allocation succeeded, over-booking the bed.', 'Local / staging');
        $this->issue($project, $teams['backend'], $sprint1, $owner, $tanvir, 'HMS-8', 'Role-based access control', 'task', 'done', 'high', 3, $now->copy()->subDays(31), $now->copy()->subDays(24), 'Owner, warden, supervisor and resident roles gate every action.');
        $this->issue($project, $teams['backend'], $sprint1, $owner, $sabbir, 'HMS-9', 'Monthly fee invoice generation', 'story', 'done', 'high', 5, $now->copy()->subDays(28), $now->copy()->subDays(20), 'Generate a per-resident monthly invoice from room rate + charges.', $epicBilling->id);

        // --- Sprint 2 (active) ------------------------------------------
        $this->issue($project, $teams['backend'], $sprint2, $owner, $tanvir, 'HMS-10', 'Record online fee payments', 'story', 'in_progress', 'high', 5, $now->copy()->subDays(6), $now->copy()->subDays(1), 'Mark invoices paid and store transaction reference + method.', $epicBilling->id);
        $paymentStory = Issue::where('key', 'HMS-10')->first();
        $this->issue($project, $teams['backend'], $sprint2, $owner, $sabbir, 'HMS-11', 'Generate payment receipt PDF', 'subtask', 'done', 'medium', 2, $now->copy()->subDays(5), $now->copy()->subDays(2), 'Downloadable receipt once a payment is recorded.', $paymentStory->id);
        $dashTask = $this->issue($project, $teams['frontend'], $sprint2, $owner, $ayesha, 'HMS-12', 'Resident dashboard: dues & room', 'task', 'done', 'medium', 3, $now->copy()->subDays(6), $now->copy()->subDays(2), 'Resident sees their room, current dues and payment history.');
        $this->issue($project, $teams['qa'], $sprint2, $owner, $nusrat, 'HMS-13', 'Invoice total ignores late fee', 'bug', 'review', 'high', 2, $now->copy()->subDays(3), $now->copy()->subHours(20),
            'Late fee is not added to the invoice grand total.', null, 'major', 'Generate an invoice past its due date and check the total.', 'Total includes the configured late fee.', 'Total excludes the late fee.', 'Staging');
        $this->issue($project, $teams['backend'], $sprint2, $owner, $sabbir, 'HMS-14', 'Vacate / checkout workflow', 'story', 'done', 'medium', 5, $now->copy()->subDays(7), $now->copy()->subDays(3), 'Free the bed, settle dues and archive the allocation on checkout.', $epicOnboard->id);
        $this->issue($project, $teams['frontend'], $sprint2, $owner, $farhana, 'HMS-15', 'Room availability board', 'task', 'review', 'medium', 3, $now->copy()->subDays(4), $now->copy()->subHours(30), 'Visual grid of occupied / free beds per block.');
        $this->issue($project, $teams['frontend'], $sprint2, $owner, $ayesha, 'HMS-16', 'Fee summary widget', 'subtask', 'done', 'low', 1, $now->copy()->subDays(5), $now->copy()->subDays(2), 'Small card on the dashboard summarising outstanding dues.', $dashTask->id);
        $this->issue($project, $teams['backend'], $sprint2, $owner, $tanvir, 'HMS-21', 'Warden approval before allocation', 'story', 'selected', 'medium', 3, $now->copy()->subDays(2), $now->copy()->subDays(2), 'Allocation waits for warden sign-off before the bed is reserved.', $epicOnboard->id);
        $this->issue($project, $teams['frontend'], $sprint2, $owner, $farhana, 'HMS-22', 'Room search & filter UI', 'task', 'done', 'low', 3, $now->copy()->subDays(5), $now->copy()->subDays(1), 'Filter rooms by block, capacity and availability.');

        // --- Sprint 3 (planned) -----------------------------------------
        $this->issue($project, $teams['frontend'], $sprint3, $owner, $farhana, 'HMS-17', 'Complaint submission & tracking', 'story', 'backlog', 'medium', 5, $now->copy()->subDays(4), $now->copy()->subDays(4), 'Residents raise complaints; staff update status until resolved.', $epicNotices->id);
        $this->issue($project, $teams['frontend'], $sprint3, $owner, $ayesha, 'HMS-18', 'Notice board publishing', 'story', 'selected', 'medium', 3, $now->copy()->subDays(3), $now->copy()->subDays(3), 'Staff publish dated notices visible to all residents.', $epicNotices->id);
        $this->issue($project, $teams['docs'], $sprint3, $owner, $farhana, 'HMS-19', 'Monthly occupancy report', 'task', 'backlog', 'low', 3, $now->copy()->subDays(3), $now->copy()->subDays(3), 'Occupancy and revenue summary per hall for the month.');
        $this->issue($project, $teams['qa'], $sprint3, $owner, $nusrat, 'HMS-20', 'Notice date shows wrong timezone', 'bug', 'backlog', 'low', 1, $now->copy()->subDays(2), $now->copy()->subDays(2),
            'Notice publish time is off by the server timezone offset.', null, 'minor', 'Publish a notice and compare the shown time to the wall clock.', 'Local publish time is shown.', 'UTC time is shown instead of local.', 'Production');

        // --- Comments ---------------------------------------------------
        $this->comment('HMS-6', $owner, 'Make sure gender-restricted halls are enforced at allocation, not just in the UI.');
        $this->comment('HMS-6', $sabbir, 'Done — allocation now checks the hall gender policy before reserving a bed.');
        $this->comment('HMS-10', $tanvir, 'Storing method (cash / bkash / bank) and a reference string. Reconciliation report can come later.');
        $this->comment('HMS-13', $nusrat, 'Reproduced on staging: RM-118 invoice total is 4,500 but should be 5,000 with the 500 late fee.');
        $this->comment('HMS-15', $farhana, 'Colour legend: green = free, amber = reserved, red = occupied. Feedback welcome before I finalise.');
        $this->comment('HMS-17', $owner, 'Complaints should notify the assigned supervisor. Keep the status list short: open / in progress / resolved.');

        // --- Recent activity (user-attributed; trigger logs are anonymous)
        $feed = [
            ['HMS-14', $sabbir, 'status_changed', ['status' => 'review'], ['status' => 'done'], $now->copy()->subDays(3)],
            ['HMS-11', $sabbir, 'status_changed', ['status' => 'in_progress'], ['status' => 'done'], $now->copy()->subDays(2)],
            ['HMS-12', $ayesha, 'status_changed', ['status' => 'review'], ['status' => 'done'], $now->copy()->subDays(2)],
            ['HMS-13', $nusrat, 'issue_created', null, ['status' => 'in_progress', 'title' => 'Invoice total ignores late fee'], $now->copy()->subDays(3)],
            ['HMS-15', $farhana, 'status_changed', ['status' => 'in_progress'], ['status' => 'review'], $now->copy()->subHours(30)],
            ['HMS-13', $tanvir, 'status_changed', ['status' => 'in_progress'], ['status' => 'review'], $now->copy()->subHours(20)],
            ['HMS-10', $tanvir, 'status_changed', ['status' => 'selected'], ['status' => 'in_progress'], $now->copy()->subDays(1)],
            ['HMS-21', $owner, 'issue_created', null, ['status' => 'selected', 'title' => 'Warden approval before allocation'], $now->copy()->subDays(2)],
        ];

        foreach ($feed as [$key, $user, $action, $old, $new, $at]) {
            $issue = Issue::where('key', $key)->first();
            ActivityLog::create([
                'project_id' => $project->id,
                'issue_id' => $issue?->id,
                'user_id' => $user->id,
                'action' => $action,
                'subject_type' => Issue::class,
                'subject_id' => $issue?->id,
                'old_values' => $old,
                'new_values' => $new,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }

        $this->command?->info('Seeded HMS workspace: 7 members, 4 teams, 3 sprints, 22 issues, comments and activity.');
    }

    private function issue(Project $project, Team $team, ?Sprint $sprint, User $reporter, User $assignee, string $key, string $title, string $type, string $status, string $priority, ?int $points, Carbon $createdAt, Carbon $updatedAt, ?string $description = null, ?string $parentId = null, ?string $severity = null, ?string $steps = null, ?string $expected = null, ?string $actual = null, ?string $environment = null): Issue
    {
        $issue = Issue::create([
            'project_id' => $project->id,
            'team_id' => $team->id,
            'sprint_id' => $sprint?->id,
            'reporter_id' => $reporter->id,
            'assignee_id' => $assignee->id,
            'parent_issue_id' => $parentId,
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'status' => $status,
            'priority' => $priority,
            'story_points' => $points,
            'severity' => $severity,
            'steps_to_reproduce' => $steps,
            'expected_result' => $expected,
            'actual_result' => $actual,
            'environment' => $environment,
        ]);

        // Backdate timestamps so cycle-time / velocity reports look real.
        DB::table('issues')->where('id', $issue->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        return $issue;
    }

    private function comment(string $issueKey, User $user, string $body): void
    {
        $issue = Issue::where('key', $issueKey)->first();

        if (! $issue) {
            return;
        }

        Comment::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);
    }
}
