<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION count_open_issues(p_project_id VARCHAR2)
            RETURN NUMBER
            IS
                v_count NUMBER;
            BEGIN
                SELECT COUNT(*)
                INTO v_count
                FROM issues
                WHERE project_id = p_project_id
                  AND status != 'done';

                RETURN v_count;
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE PROCEDURE update_issue_status(
                p_issue_id VARCHAR2,
                p_status VARCHAR2
            )
            IS
            BEGIN
                UPDATE issues
                SET status = p_status,
                    updated_at = SYSTIMESTAMP
                WHERE id = p_issue_id;
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE TRIGGER trg_project_members_role_chk
            BEFORE INSERT OR UPDATE OF role ON project_members
            FOR EACH ROW
            BEGIN
                IF :NEW.role NOT IN (
                    'admin',
                    'project_owner',
                    'scrum_master',
                    'developer',
                    'viewer'
                ) THEN
                    RAISE_APPLICATION_ERROR(-20001, 'Invalid project member role: ' || :NEW.role);
                END IF;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER trg_project_members_role_chk');
        DB::unprepared('DROP PROCEDURE update_issue_status');
        DB::unprepared('DROP FUNCTION count_open_issues');
    }
};
