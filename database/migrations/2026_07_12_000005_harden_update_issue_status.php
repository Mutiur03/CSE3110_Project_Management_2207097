<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * #10 (wiring) - Redefine update_issue_status so it validates its inputs at the
 * database layer using catalogued RAISE_APPLICATION_ERROR codes. This is
 * defense-in-depth: even a raw SQL / proc caller that bypasses the app's
 * validation gets a catalogued ORA-20014 / ORA-20010 instead of a silent bad write.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE PROCEDURE update_issue_status(
                p_issue_id VARCHAR2,
                p_status   VARCHAR2
            )
            IS
                v_exists NUMBER;
            BEGIN
                IF p_status NOT IN ('backlog', 'selected', 'in_progress', 'review', 'done') THEN
                    RAISE_APPLICATION_ERROR(
                        -20010,
                        'Invalid issue status: ' || p_status
                    );
                END IF;

                SELECT COUNT(*) INTO v_exists FROM issues WHERE id = p_issue_id;

                IF v_exists = 0 THEN
                    RAISE_APPLICATION_ERROR(
                        -20014,
                        'Issue not found: ' || p_issue_id
                    );
                END IF;

                UPDATE issues
                SET status = p_status,
                    updated_at = SYSTIMESTAMP
                WHERE id = p_issue_id;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared("
            CREATE OR REPLACE PROCEDURE update_issue_status(
                p_issue_id VARCHAR2,
                p_status   VARCHAR2
            )
            IS
            BEGIN
                UPDATE issues
                SET status = p_status,
                    updated_at = SYSTIMESTAMP
                WHERE id = p_issue_id;
            END;
        ");
    }
};
