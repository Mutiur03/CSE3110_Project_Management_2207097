<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE PROCEDURE rollup_parent_issue_status(
                p_issue_id VARCHAR2,
                p_new_status VARCHAR2
            )
            IS
                v_parent_id VARCHAR2(36);
                v_parent_status VARCHAR2(255);
                v_child_total NUMBER;
                v_child_done NUMBER;
                v_child_active NUMBER;
                v_child_selected NUMBER;
                v_new_status VARCHAR2(255);
            BEGIN
                BEGIN
                    SELECT parent_issue_id
                    INTO v_parent_id
                    FROM issues
                    WHERE id = p_issue_id;
                EXCEPTION
                    WHEN NO_DATA_FOUND THEN
                        RETURN;
                END;

                IF v_parent_id IS NULL THEN
                    RETURN;
                END IF;

                BEGIN
                    SELECT status
                    INTO v_parent_status
                    FROM issues
                    WHERE id = v_parent_id;
                EXCEPTION
                    WHEN NO_DATA_FOUND THEN
                        RETURN;
                END;

                SELECT COUNT(*),
                       NVL(SUM(CASE
                           WHEN CASE
                                    WHEN id = p_issue_id THEN p_new_status
                                    ELSE status
                                END = 'done' THEN 1
                           ELSE 0
                       END), 0),
                       NVL(SUM(CASE
                           WHEN CASE
                                    WHEN id = p_issue_id THEN p_new_status
                                    ELSE status
                                END IN ('in_progress', 'review') THEN 1
                           ELSE 0
                       END), 0),
                       NVL(SUM(CASE
                           WHEN CASE
                                    WHEN id = p_issue_id THEN p_new_status
                                    ELSE status
                                END = 'selected' THEN 1
                           ELSE 0
                       END), 0)
                INTO v_child_total,
                     v_child_done,
                     v_child_active,
                     v_child_selected
                FROM issues
                WHERE parent_issue_id = v_parent_id;

                IF v_child_total = 0 THEN
                    RETURN;
                END IF;

                IF v_child_done = v_child_total THEN
                    v_new_status := 'done';
                ELSIF v_child_active > 0 THEN
                    v_new_status := 'in_progress';
                ELSIF v_child_selected > 0 THEN
                    v_new_status := 'selected';
                ELSE
                    v_new_status := 'backlog';
                END IF;

                IF v_new_status != v_parent_status THEN
                    UPDATE issues
                    SET status = v_new_status,
                        updated_at = SYSTIMESTAMP
                    WHERE id = v_parent_id;
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE OR REPLACE TRIGGER trg_issues_status_rollup
            FOR UPDATE OF status ON issues
            COMPOUND TRIGGER

                TYPE t_issue_change IS RECORD (
                    issue_id issues.id%TYPE,
                    new_status issues.status%TYPE
                );
                TYPE t_issue_changes IS TABLE OF t_issue_change INDEX BY PLS_INTEGER;
                g_changes t_issue_changes;

                AFTER EACH ROW IS
                BEGIN
                    IF :OLD.status != :NEW.status THEN
                        g_changes(g_changes.COUNT + 1).issue_id := :NEW.id;
                        g_changes(g_changes.COUNT).new_status := :NEW.status;
                    END IF;
                END AFTER EACH ROW;

                AFTER STATEMENT IS
                BEGIN
                    FOR i IN 1 .. g_changes.COUNT LOOP
                        rollup_parent_issue_status(
                            g_changes(i).issue_id,
                            g_changes(i).new_status
                        );
                    END LOOP;

                    g_changes.DELETE;
                END AFTER STATEMENT;

            END trg_issues_status_rollup;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER trg_issues_status_rollup');
        DB::unprepared('DROP PROCEDURE rollup_parent_issue_status');
    }
};
