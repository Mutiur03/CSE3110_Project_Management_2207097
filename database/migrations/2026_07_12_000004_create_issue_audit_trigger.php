<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * #4 - DB-enforced audit trail.
 *
 * trg_issues_audit fires on every INSERT / UPDATE / DELETE of an issue and
 * writes an activity_logs row in the SAME transaction, so the history cannot
 * drift from the data even when a change comes from raw SQL, a proc, or the
 * status-rollup trigger. old_values / new_values hold the changed fields as
 * a compact JSON-ish string.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE TRIGGER trg_issues_audit
            AFTER INSERT OR UPDATE OR DELETE ON issues
            FOR EACH ROW
            DECLARE
                v_action     VARCHAR2(255);
                v_old        VARCHAR2(4000);
                v_new        VARCHAR2(4000);
                v_project_id VARCHAR2(36);
                v_issue_id   VARCHAR2(36);
            BEGIN
                IF INSERTING THEN
                    v_action     := 'issue_created';
                    v_project_id := :NEW.project_id;
                    v_issue_id   := :NEW.id;
                    v_old        := NULL;
                    v_new        := '{\"status\":\"' || :NEW.status ||
                                    '\",\"title\":\"' || SUBSTR(:NEW.title, 1, 200) || '\"}';

                ELSIF DELETING THEN
                    v_action     := 'issue_deleted';
                    v_project_id := :OLD.project_id;
                    v_issue_id   := :OLD.id;
                    v_old        := '{\"status\":\"' || :OLD.status ||
                                    '\",\"title\":\"' || SUBSTR(:OLD.title, 1, 200) || '\"}';
                    v_new        := NULL;

                ELSE
                    v_project_id := :NEW.project_id;
                    v_issue_id   := :NEW.id;

                    IF NVL(:OLD.status, '~')      = NVL(:NEW.status, '~')
                       AND NVL(:OLD.assignee_id, '~') = NVL(:NEW.assignee_id, '~')
                       AND NVL(:OLD.priority, '~')    = NVL(:NEW.priority, '~')
                       AND NVL(:OLD.sprint_id, '~')   = NVL(:NEW.sprint_id, '~') THEN
                        RETURN;
                    END IF;

                    v_action := CASE
                        WHEN NVL(:OLD.status, '~') != NVL(:NEW.status, '~') THEN 'status_changed'
                        ELSE 'issue_updated'
                    END;

                    v_old := '{\"status\":\"'     || :OLD.status ||
                             '\",\"assignee\":\"'  || NVL(:OLD.assignee_id, '') ||
                             '\",\"priority\":\"'  || :OLD.priority ||
                             '\",\"sprint\":\"'    || NVL(:OLD.sprint_id, '') || '\"}';
                    v_new := '{\"status\":\"'     || :NEW.status ||
                             '\",\"assignee\":\"'  || NVL(:NEW.assignee_id, '') ||
                             '\",\"priority\":\"'  || :NEW.priority ||
                             '\",\"sprint\":\"'    || NVL(:NEW.sprint_id, '') || '\"}';
                END IF;

                INSERT INTO activity_logs (
                    id, project_id, issue_id, action,
                    subject_type, subject_id, old_values, new_values,
                    created_at, updated_at
                ) VALUES (
                    fn_new_uuid, v_project_id, v_issue_id, v_action,
                    'issue', v_issue_id, v_old, v_new,
                    SYSTIMESTAMP, SYSTIMESTAMP
                );
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER trg_issues_audit');
    }
};
