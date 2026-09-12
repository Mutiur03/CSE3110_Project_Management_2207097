# ScrumLab

ScrumLab is a Scrum project management application built with Laravel and Oracle Database. It brings project workspaces, teams, sprint planning, a drag-and-drop board, hierarchical issues, reports, activity history, and real-time notifications into one role-aware interface.

The database layer is a core part of the project: Oracle SQL and PL/SQL implement validation, status rollups, auditing, error logging, and reporting metrics alongside the Laravel application.

## Features

- Project workspaces with active and archived states
- Role-based access for project owners, Scrum masters, developers, viewers, and admins
- Project and team membership management
- Epics, stories, tasks, subtasks, and detailed bug reports
- Parent-child issue hierarchy with automatic parent status rollup
- Backlog and sprint planning workflows
- Drag-and-drop Scrum board with Backlog, Selected, In Progress, Review, and Done columns
- Sprint lifecycle management: planned, active, and completed
- Comments, activity history, and database-level issue auditing
- Project reports for progress, health, velocity, team workload, and member performance
- Database and broadcast notifications through Laravel Reverb
- Registration, login, password reset, and profile management
- UUID primary keys throughout the application schema

## Technology

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Livewire 4 |
| Database | Oracle Database, OCI8, `yajra/laravel-oci8` |
| Database logic | Oracle SQL and PL/SQL |
| Frontend | Blade, Tailwind CSS 4, JavaScript |
| Assets | Vite 7 |
| Real-time updates | Laravel Reverb, Echo, and Pusher JS |
| Testing | PHPUnit 11 with an isolated Oracle user |

## Requirements

- PHP 8.2 or newer with the OCI8 extension
- Composer
- Node.js and npm
- Oracle Database (Oracle XE 21c works for local development)
- Oracle Instant Client 19 or newer

On Windows, the included Oracle setup script expects a 64-bit XAMPP/PHP installation with `php` available on `PATH`. SQL Developer is optional but useful for inspecting the schema and PL/SQL objects.

## Installation

### 1. Create an Oracle user

Connect as `SYSTEM` or another DBA user and create a dedicated schema for ScrumLab:

```sql
CREATE USER scrumlab IDENTIFIED BY your_password;
GRANT CONNECT, RESOURCE TO scrumlab;
GRANT UNLIMITED TABLESPACE TO scrumlab;
```

For Oracle XE, the service name is commonly `XE` or `XEPDB1`. Use the value configured by your installation or supplied by your DBA.

### 2. Install the project

```powershell
git clone <repository-url> Project_Management
cd Project_Management
composer install
Copy-Item .env.example .env
npm install
```

On a new Windows development machine, enable OCI8 and install Oracle Instant Client with:

```powershell
composer run setup-oracle
```

The script enables `extension=oci8_19`, downloads Instant Client 19 when necessary, copies the required DLLs into the active PHP directory, and verifies that OCI8 loads.

Confirm the extension is available:

```powershell
php -m | Select-String oci8
```

### 3. Configure the environment

Update the Oracle connection in `.env`:

```env
APP_NAME=ScrumLab
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=oracle
DB_HOST=127.0.0.1
DB_PORT=1521
DB_DATABASE=SCRUMLAB
DB_SERVICE_NAME=XE
DB_USERNAME=scrumlab
DB_PASSWORD=your_password
DB_CHARSET=AL32UTF8
DB_SERVER_VERSION=21c
```

`DB_SERVICE_NAME` must match the Oracle service you connect to. `DB_DATABASE` is retained by the Oracle connection configuration and is commonly the schema name or TNS database value used in your environment.

Generate the application key and clear cached configuration:

```powershell
php artisan key:generate
php artisan config:clear
```

### 4. Create and seed the database

```powershell
php artisan migrate --seed
```

This creates the Laravel authentication tables, ScrumLab domain tables, Oracle views, functions, procedures, and triggers. It also loads a demonstration project with teams, a sprint, issues, comments, and activity records.

Seeded credentials:

```text
Email:    test@example.com
Password: password
```

### 5. Run ScrumLab

Start the application server, Reverb server, and Vite development server together:

```powershell
composer run dev
```

Open <http://127.0.0.1:8000>.

For a simpler session without asset hot reloading or real-time broadcasts:

```powershell
npm run build
php artisan serve
```

## Database design

The application uses Oracle-native migrations and stores UUIDs as `VARCHAR2(36)`. Its main domain relationships are:

```text
users
  |-- own --> projects
  |            |-- project_members --> users
  |            |-- teams --> team_members --> users
  |            |-- sprints
  |            |-- issues --> child issues
  |            |     |-- comments
  |            |     `-- activity_logs
  |            `-- activity_logs
  `-- notifications
```

Core tables include `projects`, `project_members`, `teams`, `team_members`, `sprints`, `issues`, `comments`, `activity_logs`, and `notifications`, in addition to the authentication and session tables.

### PL/SQL objects

Migrations create and maintain the following Oracle objects:

| Object | Purpose |
| --- | --- |
| `count_open_issues` | Counts unfinished issues in a project |
| `update_issue_status` | Validates and updates an issue status with error handling |
| `rollup_parent_issue_status` | Recalculates a parent issue from its child statuses |
| `fn_sprint_velocity` | Returns completed story points for a sprint |
| `fn_issue_cycle_time` | Calculates the completion cycle time of an issue |
| `fn_project_progress_pct` | Calculates project completion percentage |
| `fn_project_health` | Derives a project health label from progress and overdue work |
| `fn_new_uuid` | Generates database-side UUID values |
| `proc_log_error` | Writes errors through an autonomous transaction |
| `trg_project_members_role_chk` | Rejects invalid project roles |
| `trg_issues_status_rollup` | Starts parent status rollup after child updates |
| `trg_issues_audit` | Records issue changes in the activity log |
| `v_issue_full` | Presents issues with related project, sprint, team, and user data |
| `v_project_stats` | Presents project-level issue and sprint statistics |

You can inspect installed objects in SQL Developer:

```sql
SELECT object_name, object_type, status
FROM user_objects
WHERE object_type IN ('FUNCTION', 'PROCEDURE', 'TRIGGER', 'VIEW')
ORDER BY object_type, object_name;
```

## Roles and permissions

| Role | Access |
| --- | --- |
| Project owner | Full project, member, team, sprint, and issue management |
| Scrum master | Manages the project workflow, members, teams, sprints, and issues |
| Admin | Project management access equivalent to other management roles |
| Developer | Creates and updates project work without managing the project itself |
| Viewer | Read-only access to the project workspace |

Archived projects are read-only for all roles until a manager reactivates them.

## Testing

Tests run against a separate Oracle user so development data is not refreshed or deleted.

```powershell
Copy-Item .env.testing.example .env.testing
```

In `.env.testing`:

1. Copy a valid `APP_KEY` from `.env`.
2. Keep `DB_USERNAME` different from the development username.
3. Set the Oracle test-user password.
4. Add `ORACLE_DBA_USERNAME` and `ORACLE_DBA_PASSWORD` if the test user must be created automatically.

Then run:

```powershell
composer test
```

The test bootstrap provisions the configured Oracle test user when DBA credentials are available. You can also provision it explicitly:

```powershell
php artisan oracle:ensure-test-user
```

If DBA access is unavailable, ask your DBA to create the test user or adapt `database/oracle/create_test_user.sql`.

## Useful commands

| Command | Description |
| --- | --- |
| `composer run setup-oracle` | Configure OCI8 and Instant Client on Windows |
| `composer run dev` | Run Laravel, Reverb, and Vite concurrently |
| `composer test` | Clear configuration and run the test suite |
| `npm run dev` | Run only the Vite development server |
| `npm run build` | Create production frontend assets |
| `php artisan migrate --seed` | Build the complete database and load demo data |
| `php artisan db:seed --class='Database\Seeders\HmsDemoSeeder'` | Seed the optional HMS demo into an existing `HMS` project |
| `php artisan config:clear` | Clear cached configuration after `.env` changes |

## Troubleshooting

| Problem | Resolution |
| --- | --- |
| `php` is not recognized | Add the PHP directory (for example, `C:\xampp\php`) to `PATH` and reopen the terminal |
| `Undefined constant OCI_DEFAULT` | Run `composer run setup-oracle` and confirm `php -m` lists `oci8` |
| OCI8 reports that a procedure could not be found | Ensure the Instant Client DLLs match PHP's architecture; rerun the setup script |
| `ORA-12541: TNS:no listener` | Start the Oracle listener/service and verify the host and port |
| `ORA-12514` or `ORA-12154` | Correct `DB_SERVICE_NAME` or the configured Oracle service/TNS value |
| `ORA-01017: invalid username/password` | Verify `DB_USERNAME` and `DB_PASSWORD` in the active environment file |
| `ORA-00942: table or view does not exist` | Run `php artisan migrate` using the expected Oracle schema |
| PL/SQL object is missing or invalid | Run all migrations, then inspect `USER_OBJECTS` and `USER_ERRORS` in Oracle |
| Environment changes are ignored | Run `php artisan config:clear` |
| Real-time notifications do not update | Use `composer run dev`, or start `php artisan reverb:start` separately |

## Project structure

```text
app/                 Controllers, models, Livewire, events, notifications, support code
database/migrations/ Oracle tables, views, functions, procedures, and triggers
database/oracle/     Oracle helper scripts
database/seeders/    Default and optional demonstration data
resources/views/     Blade pages and reusable UI components
resources/css/       Tailwind application styles
resources/js/        Vite, Echo, and browser-side behavior
routes/              HTTP, console, and broadcast-channel routes
scripts/             Windows Oracle/PHP setup automation
tests/               PHPUnit unit and feature tests
```

## License

This project is built on Laravel, which is licensed under the [MIT License](https://opensource.org/licenses/MIT).
