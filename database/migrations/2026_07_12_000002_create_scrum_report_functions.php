<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * #5 - Reporting / metric functions callable from SELECT ... FROM DUAL.
 *
 * fn_sprint_velocity   : story points completed in a sprint.
 * fn_issue_cycle_time  : whole days from issue creation to completion (NULL if not done).
 * fn_project_progress_pct : percent of a project's issues that are done.
 * fn_project_health    : coarse health label derived from progress + overdue work.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_sprint_velocity(p_sprint_id VARCHAR2)
            RETURN NUMBER
            IS
                v_points NUMBER;
            BEGIN
                SELECT NVL(SUM(story_points), 0)
                INTO v_points
                FROM issues
                WHERE sprint_id = p_sprint_id
                  AND status = 'done';

                RETURN v_points;
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_issue_cycle_time(p_issue_id VARCHAR2)
            RETURN NUMBER
            IS
                v_status  issues.status%TYPE;
                v_created issues.created_at%TYPE;
                v_updated issues.updated_at%TYPE;
            BEGIN
                SELECT status, created_at, updated_at
                INTO v_status, v_created, v_updated
                FROM issues
                WHERE id = p_issue_id;

                IF v_status != 'done' OR v_created IS NULL OR v_updated IS NULL THEN
                    RETURN NULL;
                END IF;

                -- Casting TIMESTAMPs to DATE lets us subtract to a day count.
                RETURN CAST(v_updated AS DATE) - CAST(v_created AS DATE);
            EXCEPTION
                WHEN NO_DATA_FOUND THEN
                    RETURN NULL;
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_project_progress_pct(p_project_id VARCHAR2)
            RETURN NUMBER
            IS
                v_total NUMBER;
                v_done  NUMBER;
            BEGIN
                SELECT COUNT(*),
                       NVL(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0)
                INTO v_total, v_done
                FROM issues
                WHERE project_id = p_project_id;

                IF v_total = 0 THEN
                    RETURN 0;
                END IF;

                RETURN ROUND((v_done / v_total) * 100);
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_project_health(p_project_id VARCHAR2)
            RETURN VARCHAR2
            IS
                v_total   NUMBER;
                v_done    NUMBER;
                v_overdue NUMBER;
            BEGIN
                SELECT COUNT(*),
                       NVL(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0)
                INTO v_total, v_done
                FROM issues
                WHERE project_id = p_project_id;

                IF v_total = 0 THEN
                    RETURN 'empty';
                END IF;

                -- Unfinished issues sitting in a sprint whose end date has passed.
                SELECT COUNT(*)
                INTO v_overdue
                FROM issues i
                JOIN sprints s ON s.id = i.sprint_id
                WHERE i.project_id = p_project_id
                  AND i.status != 'done'
                  AND s.end_date < TRUNC(SYSDATE);

                IF v_done = v_total THEN
                    RETURN 'complete';
                ELSIF v_overdue > 0 THEN
                    RETURN 'at_risk';
                ELSIF (v_done / v_total) >= 0.5 THEN
                    RETURN 'on_track';
                ELSE
                    RETURN 'behind';
                END IF;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION fn_project_health');
        DB::unprepared('DROP FUNCTION fn_project_progress_pct');
        DB::unprepared('DROP FUNCTION fn_issue_cycle_time');
        DB::unprepared('DROP FUNCTION fn_sprint_velocity');
    }
};
