<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * #10 - Error / edge PL/SQL core.
 *
 * fn_new_uuid   : generates a lower-case, dashed UUID (36 chars) from SYS_GUID,
 *                 so DB-side inserts match the app's VARCHAR2(36) UUID keys.
 * proc_log_error: autonomous-transaction logger that survives a rollback.
 *
 * Error codes used by update_issue_status (RAISE_APPLICATION_ERROR range -20000..-20999):
 *   -20010 invalid status, -20014 issue not found.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_new_uuid
            RETURN VARCHAR2
            IS
                v_hex VARCHAR2(32);
            BEGIN
                v_hex := LOWER(RAWTOHEX(SYS_GUID()));
                RETURN SUBSTR(v_hex, 1, 8)  || '-' ||
                       SUBSTR(v_hex, 9, 4)  || '-' ||
                       SUBSTR(v_hex, 13, 4) || '-' ||
                       SUBSTR(v_hex, 17, 4) || '-' ||
                       SUBSTR(v_hex, 21, 12);
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE PROCEDURE proc_log_error(
                p_context VARCHAR2,
                p_message VARCHAR2
            ) IS
                PRAGMA AUTONOMOUS_TRANSACTION;
            BEGIN
                INSERT INTO activity_logs (
                    id, action, subject_type, new_values, created_at, updated_at
                ) VALUES (
                    fn_new_uuid,
                    'error',
                    SUBSTR(p_context, 1, 255),
                    SUBSTR(p_message, 1, 4000),
                    SYSTIMESTAMP,
                    SYSTIMESTAMP
                );
                COMMIT;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE proc_log_error');
        DB::unprepared('DROP FUNCTION fn_new_uuid');
    }
};
