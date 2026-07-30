# Local SQLite-to-MySQL migration assessment

## Purpose and boundary

This runbook moved the local **application runtime** from SQLite to the
installed local MySQL server before LifeDiff product migrations begin. No
application code, migrations, or test settings changed.

Migration status (2026-07-30): completed for the local application runtime.
MySQL startup and administrator-level database/user provisioning required manual
intervention. Laravel now uses the ignored local `.env` MySQL connection.

The desired destination must match the intended deployment family: MySQL 8.x,
strict SQL mode, `utf8mb4`, and the selected production collation. Confirm the
deployment provider's exact MySQL version, charset, collation, and connection
method before treating local parity as complete.

## Findings

| Area                           | Current state                                                                                                   | Consequence                                                                                           |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Application default connection | MySQL                                                                                                           | Local requests use `lifediff_local` through the dedicated local application user.                     |
| SQLite database                | 116 KB; 10 starter tables; no application rows; five migrations applied                                         | It is retained as a rollback artifact; no data conversion was needed.                                 |
| Local MySQL installation       | MySQL Community Server 8.4.1 client and server files installed at `/usr/local/mysql`                            | Suitable runtime is installed.                                                                        |
| Local MySQL service            | Running and reachable by Laravel over TCP                                                                       | The service wrapper does not find its PID file; verify application reachability with Laravel instead. |
| PHP drivers                    | `pdo_mysql` and `pdo_sqlite` enabled                                                                            | No PHP extension or Composer dependency is needed.                                                    |
| Laravel MySQL connection       | Already defined in `config/database.php` with strict mode, `utf8mb4`, and `utf8mb4_unicode_ci` defaults         | No application configuration file change is needed.                                                   |
| Sessions, cache, queues        | All use the database driver locally                                                                             | Migrating runs their tables on MySQL too; do not carry active local sessions/jobs across.             |
| Automated tests                | `phpunit.xml` explicitly uses in-memory SQLite                                                                  | Application runtime parity does not yet give test-suite database parity.                              |
| Starter migrations             | Use Laravel schema APIs, foreign keys, JSON, timestamps, and unsigned integers; no SQLite-specific branch found | They should run on MySQL 8.4; verify on the new database before Phase 1 migrations.                   |

## Execution result

- `lifediff_local` and `lifediff_testing` are reachable through the dedicated
  local MySQL account. Credentials remain only in ignored local configuration.
- All nine current Laravel migrations completed on `lifediff_local`, creating
  the expected thirteen tables, including the Phase 1 profile, repository,
  release, and change-entry schema.
- Laravel cache completed a reversible database-backed write/delete smoke check
  on MySQL.
- The existing suite remains green: 39 tests and 136 assertions. It still uses
  in-memory SQLite by design until the separate test-parity change is approved.

## Recommended migration procedure

### 1. Preserve the SQLite rollback point

Do not delete or overwrite `database/database.sqlite`. It is ignored by Git and
currently contains no user, session, job, passkey, cache, or application data,
so no export/import operation is warranted. Make a dated local copy before the
switch if the file later gains data.

If SQLite becomes the active database again, restore the `.env` SQLite settings
and clear Laravel's configuration cache. No migration rollback is required for
the current empty database.

### 2. Start and verify the local MySQL service

The installed service script supports `start`, `stop`, `restart`, `reload`,
`force-reload`, and `status`. Start the server through the MySQL preference pane
or the local installation's service-management procedure, then verify it with:

```bash
/usr/local/mysql/support-files/mysql.server status
mysqladmin ping
mysql --version
```

This migration required manual MySQL startup. If `mysqladmin ping` reports a
missing `/tmp/mysql.sock` in a future restart, inspect the server's configured
socket after startup instead of guessing a `DB_SOCKET` value.

### 3. Create separate local development and test databases

Using a MySQL administrator account, create one database for normal local work
and one disposable database for integration tests. Use `utf8mb4` and the
collation agreed with deployment; the existing Laravel default is shown below.

```sql
CREATE DATABASE lifediff_local
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE DATABASE lifediff_testing
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Create a dedicated local application account with only the database privileges
it needs. Pick **one** connection style and grant against the matching host:

- TCP: `DB_HOST=127.0.0.1` and a MySQL account scoped to `127.0.0.1`.
- Unix socket: set `DB_SOCKET` to the socket reported by the running server and
  use an account scoped to `localhost`.

Do not put administrator credentials in `.env`, commits, terminal transcripts,
or this document. Store the application's local password only in ignored local
environment configuration.

### 4. Change only local environment configuration

After the server and database account are verified, replace the SQLite database
settings in the local ignored `.env` with the selected MySQL connection values:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lifediff_local
DB_USERNAME=<local application user>
DB_PASSWORD=<local application password>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

For a socket connection, set `DB_SOCKET=<verified socket path>` and omit the
TCP-specific host/port only if the chosen server configuration requires that.
Keep `SESSION_DRIVER=database`, `CACHE_STORE=database`, and
`QUEUE_CONNECTION=database`; they will use the new default MySQL connection.

Do not change `.env.example` until the team intentionally makes MySQL the
repository's default onboarding database. Do not use `composer setup` for this
switch: its current script creates `database/database.sqlite` before migrating.

### 5. Bootstrap and verify the MySQL schema

After saving `.env`, remove Laravel's cached configuration and use the
repository-supported Artisan commands below:

```bash
php artisan config:clear
php artisan db:show
php artisan migrate:status
php artisan migrate
php artisan migrate:status
php artisan db:show --counts
```

Expected result: the five current starter migrations run against
`lifediff_local`, producing users, cache, jobs, passkeys, sessions, and
password-reset tables. The migration history must belong to MySQL, not the
SQLite file.

`php artisan migrate:fresh`, `php artisan migrate:reset`, and
`php artisan db:wipe` are destructive and are not part of the initial switch.
They are appropriate only for the disposable test database or an explicitly
approved local reset.

### 6. Smoke-test application infrastructure

Before beginning Phase 1 product work, verify that a local registration/login,
database-backed session, cache write, queued job, passkey, and two-factor flow
can reach MySQL. Existing authentication and security tests must remain green.
Do not copy a live session, queued job, cache entry, password-reset token, or
passkey data from SQLite.

### 7. Add MySQL test parity as a deliberate follow-up

The current test suite remains isolated and fast because `phpunit.xml` sets
`DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`. That is safe, but it cannot
detect MySQL-only migration or collation behavior.

Before relying on production parity, make a separately reviewed test-
infrastructure change that:

1. points test configuration to `lifediff_testing`, never `lifediff_local`;
2. ensures `RefreshDatabase` or the test bootstrap safely resets only that
   database;
3. prevents parallel test workers from sharing the same MySQL schema unless
   worker-specific databases are provisioned;
4. runs the migration suite and representative authorization/version/visibility
   tests against MySQL; and
5. keeps SQLite only if it remains an intentional secondary compatibility lane.

No change to `phpunit.xml` is included in this assessment.

## Phase 1 migration safeguards

The Phase 1 plan already identifies database decisions that must be settled
before domain migrations:

- Store enum values as strings backed by PHP enums; do not use database-native
  enums.
- Normalize handles and repository names in application-controlled columns; do
  not rely solely on collation for case-insensitive uniqueness.
- Release versions use a portable unique `(repository_id, version)` index.
  MySQL does not provide a portable partial unique index, so a soft-deleted
  version remains reserved until an explicitly approved schema migration
  changes that strategy.
- Keep database constraints, foreign keys, indexes, transactions, and strict
  mode under MySQL coverage. Test visibility reduction and publication as
  transactions.
- Do not use `schema:dump` as the source of truth while migrations are still
  evolving. If introduced later, regenerate and commit it only through an
  explicit repository decision.

## Completion checklist

- [x] MySQL server is running and reachable through the selected TCP
      connection.
- [ ] Deployment MySQL version, charset, collation, strict-mode expectation,
      and connection style are documented and matched locally.
- [x] `lifediff_local` uses a dedicated local application account.
- [x] Local `.env` selects MySQL without exposing credentials.
- [x] `php artisan migrate:status` and `php artisan db:show --counts` confirm
      the starter schema on MySQL.
- [x] Database-backed cache smoke path works with the MySQL connection; full
      local auth/security browser smoke testing remains part of Phase 1.
- [ ] Local authentication/security smoke paths work with database-backed
      session, cache, and queue drivers.
- [x] A separate disposable MySQL test database exists. The test-parity
      configuration decision remains outstanding before Phase 1 schema work
      relies on MySQL behavior.
- [x] The SQLite file is retained until the MySQL migration has been accepted.
