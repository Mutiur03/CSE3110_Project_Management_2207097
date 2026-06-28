<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE projects (
                id VARCHAR2(36) NOT NULL,
                owner_id VARCHAR2(36) NOT NULL,
                name VARCHAR2(255) NOT NULL,
                key VARCHAR2(255) NOT NULL,
                description VARCHAR2(1000) NULL,
                status VARCHAR2(255) DEFAULT 'active' NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT projects_pk PRIMARY KEY (id),
                CONSTRAINT projects_key_uk UNIQUE (key),
                CONSTRAINT projects_owner_id_fk FOREIGN KEY (owner_id)
                    REFERENCES users (id) ON DELETE CASCADE
            )
        ");

        DB::unprepared("
            CREATE TABLE project_members (
                id VARCHAR2(36) NOT NULL,
                project_id VARCHAR2(36) NOT NULL,
                user_id VARCHAR2(36) NOT NULL,
                role VARCHAR2(255) DEFAULT 'developer' NOT NULL,
                joined_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT project_members_pk PRIMARY KEY (id),
                CONSTRAINT pm_proj_user_uk UNIQUE (project_id, user_id),
                CONSTRAINT pm_project_id_fk FOREIGN KEY (project_id)
                    REFERENCES projects (id) ON DELETE CASCADE,
                CONSTRAINT pm_user_id_fk FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            )
        ");

        DB::unprepared("
            CREATE TABLE teams (
                id VARCHAR2(36) NOT NULL,
                project_id VARCHAR2(36) NOT NULL,
                name VARCHAR2(255) NOT NULL,
                description VARCHAR2(1000) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT teams_pk PRIMARY KEY (id),
                CONSTRAINT teams_project_name_uk UNIQUE (project_id, name),
                CONSTRAINT teams_project_id_fk FOREIGN KEY (project_id)
                    REFERENCES projects (id) ON DELETE CASCADE
            )
        ");

        DB::unprepared("
            CREATE TABLE team_members (
                id VARCHAR2(36) NOT NULL,
                team_id VARCHAR2(36) NOT NULL,
                user_id VARCHAR2(36) NOT NULL,
                role VARCHAR2(255) DEFAULT 'developer' NOT NULL,
                joined_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT team_members_pk PRIMARY KEY (id),
                CONSTRAINT tm_team_user_uk UNIQUE (team_id, user_id),
                CONSTRAINT tm_team_id_fk FOREIGN KEY (team_id)
                    REFERENCES teams (id) ON DELETE CASCADE,
                CONSTRAINT tm_user_id_fk FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            )
        ");

        DB::unprepared("
            CREATE TABLE sprints (
                id VARCHAR2(36) NOT NULL,
                project_id VARCHAR2(36) NOT NULL,
                name VARCHAR2(255) NOT NULL,
                goal VARCHAR2(1000) NULL,
                start_date DATE NULL,
                end_date DATE NULL,
                status VARCHAR2(255) DEFAULT 'planned' NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT sprints_pk PRIMARY KEY (id),
                CONSTRAINT sprints_project_id_fk FOREIGN KEY (project_id)
                    REFERENCES projects (id) ON DELETE CASCADE
            )
        ");

        DB::unprepared("
            CREATE TABLE issues (
                id VARCHAR2(36) NOT NULL,
                project_id VARCHAR2(36) NOT NULL,
                team_id VARCHAR2(36) NULL,
                sprint_id VARCHAR2(36) NULL,
                reporter_id VARCHAR2(36) NOT NULL,
                assignee_id VARCHAR2(36) NULL,
                parent_issue_id VARCHAR2(36) NULL,
                key VARCHAR2(255) NOT NULL,
                title VARCHAR2(255) NOT NULL,
                description VARCHAR2(4000) NULL,
                type VARCHAR2(255) DEFAULT 'task' NOT NULL,
                status VARCHAR2(255) DEFAULT 'backlog' NOT NULL,
                priority VARCHAR2(255) DEFAULT 'medium' NOT NULL,
                story_points NUMBER(5) NULL,
                severity VARCHAR2(255) NULL,
                steps_to_reproduce VARCHAR2(4000) NULL,
                expected_result VARCHAR2(4000) NULL,
                actual_result VARCHAR2(4000) NULL,
                environment VARCHAR2(255) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT issues_pk PRIMARY KEY (id),
                CONSTRAINT issues_key_uk UNIQUE (key),
                CONSTRAINT issues_project_id_fk FOREIGN KEY (project_id)
                    REFERENCES projects (id) ON DELETE CASCADE,
                CONSTRAINT issues_team_id_fk FOREIGN KEY (team_id)
                    REFERENCES teams (id) ON DELETE SET NULL,
                CONSTRAINT issues_sprint_id_fk FOREIGN KEY (sprint_id)
                    REFERENCES sprints (id) ON DELETE SET NULL,
                CONSTRAINT issues_reporter_id_fk FOREIGN KEY (reporter_id)
                    REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT issues_assignee_id_fk FOREIGN KEY (assignee_id)
                    REFERENCES users (id) ON DELETE SET NULL,
                CONSTRAINT issues_parent_fk FOREIGN KEY (parent_issue_id)
                    REFERENCES issues (id) ON DELETE SET NULL
            )
        ");

        DB::unprepared("
            CREATE TABLE comments (
                id VARCHAR2(36) NOT NULL,
                issue_id VARCHAR2(36) NOT NULL,
                user_id VARCHAR2(36) NOT NULL,
                body VARCHAR2(4000) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT comments_pk PRIMARY KEY (id),
                CONSTRAINT comments_issue_id_fk FOREIGN KEY (issue_id)
                    REFERENCES issues (id) ON DELETE CASCADE,
                CONSTRAINT comments_user_id_fk FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            )
        ");

        DB::unprepared("
            CREATE TABLE activity_logs (
                id VARCHAR2(36) NOT NULL,
                project_id VARCHAR2(36) NULL,
                issue_id VARCHAR2(36) NULL,
                user_id VARCHAR2(36) NULL,
                action VARCHAR2(255) NOT NULL,
                subject_type VARCHAR2(255) NULL,
                subject_id VARCHAR2(36) NULL,
                old_values VARCHAR2(4000) NULL,
                new_values VARCHAR2(4000) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT activity_logs_pk PRIMARY KEY (id),
                CONSTRAINT al_project_id_fk FOREIGN KEY (project_id)
                    REFERENCES projects (id) ON DELETE CASCADE,
                CONSTRAINT al_issue_id_fk FOREIGN KEY (issue_id)
                    REFERENCES issues (id) ON DELETE CASCADE,
                CONSTRAINT al_user_id_fk FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE SET NULL
            )
        ");

        DB::unprepared("
            CREATE TABLE notifications (
                id VARCHAR2(36) NOT NULL,
                type VARCHAR2(255) NOT NULL,
                notifiable_type VARCHAR2(255) NOT NULL,
                notifiable_id VARCHAR2(36) NOT NULL,
                data VARCHAR2(4000) NOT NULL,
                read_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT notifications_pk PRIMARY KEY (id)
            )
        ");

        DB::unprepared('CREATE INDEX notif_notifiable_idx ON notifications (notifiable_type, notifiable_id)');
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE notifications CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE activity_logs CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE comments CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE issues CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE sprints CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE team_members CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE teams CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE project_members CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE projects CASCADE CONSTRAINTS');
    }
};
