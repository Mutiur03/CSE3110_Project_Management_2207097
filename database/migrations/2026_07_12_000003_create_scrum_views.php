<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * #3 - Views.
 *
 * v_issue_full    : issues denormalised with project / sprint / people names,
 *                   so controllers stop repeating the same joins.
 * v_project_stats : one live row per project built on the #5 metric functions.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE VIEW v_issue_full AS
            SELECT
                i.*,
                p.key            AS project_key,
                p.name           AS project_name,
                s.name           AS sprint_name,
                s.status         AS sprint_status,
                tm.name          AS team_name,
                ua.name          AS assignee_name,
                ua.avatar        AS assignee_avatar,
                ur.name          AS reporter_name,
                fn_issue_cycle_time(i.id) AS cycle_time_days
            FROM issues i
            JOIN projects p        ON p.id  = i.project_id
            LEFT JOIN sprints s    ON s.id  = i.sprint_id
            LEFT JOIN teams tm     ON tm.id = i.team_id
            LEFT JOIN users ua     ON ua.id = i.assignee_id
            JOIN users ur          ON ur.id = i.reporter_id
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW v_project_stats AS
            SELECT
                p.id                                AS project_id,
                p.name                              AS project_name,
                p.key                               AS project_key,
                (SELECT COUNT(*) FROM issues i WHERE i.project_id = p.id)          AS issue_count,
                count_open_issues(p.id)             AS open_count,
                fn_project_progress_pct(p.id)       AS progress_pct,
                fn_project_health(p.id)             AS health,
                (SELECT COUNT(*) FROM teams t WHERE t.project_id = p.id)          AS team_count,
                (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS member_count
            FROM projects p
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW v_project_stats');
        DB::unprepared('DROP VIEW v_issue_full');
    }
};
