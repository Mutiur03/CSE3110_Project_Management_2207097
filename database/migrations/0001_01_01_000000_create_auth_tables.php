<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE users (
                id VARCHAR2(36) NOT NULL,
                name VARCHAR2(255) NOT NULL,
                email VARCHAR2(255) NOT NULL,
                email_verified_at TIMESTAMP NULL,
                password VARCHAR2(255) NOT NULL,
                avatar VARCHAR2(255) NULL,
                job_title VARCHAR2(255) NULL,
                remember_token VARCHAR2(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT users_pk PRIMARY KEY (id),
                CONSTRAINT users_email_uk UNIQUE (email)
            )
        ");

        DB::unprepared("
            CREATE TABLE password_reset_tokens (
                email VARCHAR2(255) NOT NULL,
                token VARCHAR2(255) NOT NULL,
                created_at TIMESTAMP NULL,
                CONSTRAINT password_reset_tokens_pk PRIMARY KEY (email)
            )
        ");

        DB::unprepared("
            CREATE TABLE sessions (
                id VARCHAR2(255) NOT NULL,
                user_id VARCHAR2(36) NULL,
                ip_address VARCHAR2(45) NULL,
                user_agent VARCHAR2(4000) NULL,
                payload CLOB NOT NULL,
                last_activity NUMBER(10) NOT NULL,
                CONSTRAINT sessions_pk PRIMARY KEY (id),
                CONSTRAINT sessions_user_id_fk FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE SET NULL
            )
        ");

        DB::unprepared('CREATE INDEX sessions_last_activity_idx ON sessions (last_activity)');
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE sessions CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE password_reset_tokens CASCADE CONSTRAINTS');
        DB::unprepared('DROP TABLE users CASCADE CONSTRAINTS');
    }
};
