<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## ScrumLab Setup (Windows + Oracle)

ScrumLab is a Laravel project management app that uses **Oracle Database** and **PL/SQL** for coursework. The app, migrations, tests, and raw SQL helpers are all Oracle-only (`yajra/laravel-oci8`).

### What you need installed

| Software | Purpose | Download |
|----------|---------|----------|
| **XAMPP** (PHP 8.2, 64-bit) | PHP, Apache (optional) | [apachefriends.org](https://www.apachefriends.org/) |
| **Composer** | PHP dependencies | [getcomposer.org](https://getcomposer.org/download/) |
| **Node.js** (LTS) | Frontend build (Vite) | [nodejs.org](https://nodejs.org/) |
| **Git** | Clone the repo | [git-scm.com](https://git-scm.com/) |
| **Oracle Database** | Data storage | XE 21c or school server (see below) |
| **Oracle Instant Client 19+** | PHP OCI8 libraries | [Instant Client downloads](https://www.oracle.com/database/technologies/instant-client/winx64-64-downloads.html) |
| **SQL Developer** (optional) | Run PL/SQL scripts | [SQL Developer](https://www.oracle.com/database/sqldeveloper/) |
| **Visual C++ Redistributable** | Required by Instant Client | [Microsoft VC++ Redist](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist) |

---

### Step 1 — Install Oracle Database

**Option A: Oracle Database XE 21c (local, free)**

1. Download [Oracle Database 21c XE for Windows](https://www.oracle.com/database/technologies/xe-downloads.html).
2. Run the installer and note:
   - **Port:** `1521` (default)
   - **Service name:** often `XE` (or `XEPDB1` for the pluggable DB — check your install)
3. After install, open **SQL*Plus** or SQL Developer as `SYSTEM` and create an app user:

```sql
CREATE USER scrumlab IDENTIFIED BY your_password;
GRANT CONNECT, RESOURCE TO scrumlab;
GRANT UNLIMITED TABLESPACE TO scrumlab;
```

**Option B: School Oracle server**

Use the host, port, service name, username, and password from your teacher.

---

### Step 2 — One-time PHP + Oracle setup (any new Windows PC)

Oracle needs a **one-time** PHP setup on each machine. After that, you only edit `.env`.

From the project folder, run:

```powershell
composer run setup-oracle
```

This script will:

1. Enable `extension=oci8_19` in your `php.ini`
2. Download Oracle Instant Client 19 if it is not already present
3. Copy the required DLLs into your PHP folder (so old Oracle 11.2 on PATH does not break OCI8)
4. Verify `oci8` loads with no warnings

**Manual fallback** (if the script cannot download):

```powershell
# 1. Enable in php.ini: extension=oci8_19
# 2. Extract Instant Client to C:\oracle\instantclient_19_31
Copy-Item "C:\oracle\instantclient_19_31\*.dll" "C:\xampp\php\" -Force
php -m | findstr oci8
```

---

### Step 3 — Clone and configure the project

```powershell
git clone <your-repo-url> Project_Management
cd Project_Management
composer install
composer run setup-oracle
copy .env.example .env
```

Edit `.env` with your Oracle credentials:

```env
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

| Variable | Meaning |
|----------|---------|
| `DB_HOST` | Oracle server IP or `127.0.0.1` for local XE |
| `DB_PORT` | Usually `1521` |
| `DB_SERVICE_NAME` | `XE` for XE, or value from your DBA |
| `DB_USERNAME` / `DB_PASSWORD` | App user (e.g. `scrumlab`) |
| `DB_DATABASE` | Often same as username or a TNS alias |

Generate the app key and clear config cache:

```powershell
php artisan key:generate
php artisan config:clear
```

---

### Step 4 — Create tables and sample data

```powershell
php artisan migrate --seed
```

Migrations use **raw Oracle SQL** (`DB::unprepared`) for tables, foreign keys, and PL/SQL objects (`count_open_issues` function, `update_issue_status` and `rollup_parent_issue_status` procedures, `trg_issues_status_rollup` and `trg_project_members_role_chk` triggers). Bug-report columns are included in the main `issues` table migration.

The same PL/SQL is also saved in `database/oracle/scrum_plsql.sql` if you want to run or inspect it in SQL Developer.

If migration fails:
- Check Oracle listener is running (Windows Services → `OracleServiceXE` or similar).
- Test login in SQL Developer with the same user/password.
- Run `php artisan config:clear` after any `.env` change.

---

### Step 5 — PL/SQL (course requirement)

`php artisan migrate` already creates:

- **`count_open_issues`** — function that counts non-done issues for a project
- **`update_issue_status`** — procedure that updates an issue status
- **`rollup_parent_issue_status`** — procedure that recomputes a parent issue status from its children (story/task under epic, subtask under story/task)
- **`trg_issues_status_rollup`** — trigger on `issues.status` that calls the rollup procedure when a child status changes
- **`trg_project_members_role_chk`** — trigger that validates project member roles

Optional: open `database/oracle/scrum_plsql.sql` in SQL Developer to review or re-run the same objects manually.

Verify in SQL Developer:

```sql
SELECT object_name, object_type
FROM user_objects
WHERE object_type IN ('FUNCTION', 'PROCEDURE', 'TRIGGER')
ORDER BY object_name;
```

Demo the function (use a real project UUID from the `projects` table):

```sql
SELECT id, name, count_open_issues(id) AS open_issues
FROM projects;
```

Demo the procedure:

```sql
BEGIN
    update_issue_status('issue-uuid-here', 'done');
END;
```

The Laravel app also calls these objects from PHP: `count_open_issues` on the dashboard (`DashboardController`) and `update_issue_status` when moving issues on the board, editing an issue, or changing sprint membership (`SqlDialect::updateIssueStatus()`).

---

### Step 6 — Build frontend and run the app

```powershell
npm install
npm run build
composer run dev
```

Or just the backend:

```powershell
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

**Default seeded login** (after `migrate --seed`): `test@example.com` / `password`

---

### Step 7 — Run tests

Tests use the same Oracle connection as the app. Ensure `.env` has valid `DB_*` values, then:

```powershell
php artisan test
```

`RefreshDatabase` runs `migrate:fresh` before each test class, so Oracle must be running and the app user must be able to create/drop tables.

---

### Quick checklist (new machine)

- [ ] XAMPP PHP 8.2, Composer, Node.js installed
- [ ] Oracle DB running, app user created
- [ ] `composer run setup-oracle` — shows `oci8 is ready`
- [ ] `php -m` shows `oci8`
- [ ] `.env` has correct `DB_*` values
- [ ] `php artisan migrate --seed` succeeds (tables + PL/SQL)
- [ ] `npm run build` and `php artisan serve` — app loads in browser

---

### Troubleshooting

| Error | Fix |
|-------|-----|
| `Undefined constant OCI_DEFAULT` | Run `composer run setup-oracle` on this machine |
| `The specified procedure could not be found` | Same — run `composer run setup-oracle` (copies correct DLLs into PHP folder) |
| `ORA-12541: TNS:no listener` | Start Oracle service; check `DB_HOST` / `DB_PORT` |
| `ORA-01017: invalid username/password` | Fix `DB_USERNAME` / `DB_PASSWORD` in `.env` |
| `ORA-00942: table or view does not exist` | Run `php artisan migrate` first |
| `ORA-04043: object does not exist` | Run `php artisan migrate` to create function, procedure, and trigger |
| Config changes ignored | `php artisan config:clear` |

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
