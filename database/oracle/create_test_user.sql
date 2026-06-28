-- Optional manual setup if you cannot put ORACLE_DBA_* in .env.testing.
-- Otherwise PHPUnit creates this user automatically before the first test run.

CREATE USER laravel_test IDENTIFIED BY password;

GRANT CONNECT, RESOURCE TO laravel_test;
GRANT UNLIMITED TABLESPACE TO laravel_test;
