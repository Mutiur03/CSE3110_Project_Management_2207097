<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class OracleTestUserProvisioner
{
    public static function ensure(): void
    {
        $testConfig = config('database.connections.oracle');
        $username = (string) ($testConfig['username'] ?? '');
        $password = (string) ($testConfig['password'] ?? '');

        if ($username === '') {
            throw new RuntimeException('DB_USERNAME is not set in .env.testing.');
        }

        if (self::canConnectAs('oracle')) {
            return;
        }

        if (! self::hasDbaCredentials()) {
            throw new RuntimeException(
                'Cannot connect as the test Oracle user ('.$username.'). '
                .'Add ORACLE_DBA_USERNAME and ORACLE_DBA_PASSWORD to .env.testing (local XE: often system / your SYS password) '
                .'so PHPUnit can create the test user automatically.'
            );
        }

        if (! self::userExists($username)) {
            self::createUser($username, $password);
        } elseif (! self::canConnectAs('oracle')) {
            throw new RuntimeException(
                'Oracle user '.$username.' exists but login failed. '
                .'Fix DB_PASSWORD in .env.testing or drop the user and run tests again.'
            );
        }

        DB::purge('oracle');

        if (! self::canConnectAs('oracle')) {
            throw new RuntimeException('Created Oracle test user '.$username.' but login still failed.');
        }
    }

    public static function hasDbaCredentials(): bool
    {
        $username = (string) config('database.connections.oracle_dba.username');
        $password = config('database.connections.oracle_dba.password');

        return $username !== '' && $password !== null && $password !== '';
    }

    /**
     * @return list<string>
     */
    public static function missingDbaEnvKeys(): array
    {
        $path = base_path('.env.testing');
        $values = is_file($path) ? self::readEnvFile($path) : [];
        $missing = [];

        foreach (['ORACLE_DBA_USERNAME', 'ORACLE_DBA_PASSWORD'] as $key) {
            if (! array_key_exists($key, $values) || trim((string) $values[$key]) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * @return array<string, string>
     */
    protected static function readEnvFile(string $path): array
    {
        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\"'");
        }

        return $values;
    }

    protected static function canConnectAs(string $connection): bool
    {
        try {
            DB::connection($connection)->selectOne('SELECT 1 AS one FROM DUAL');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function userExists(string $username): bool
    {
        $row = DB::connection('oracle_dba')->selectOne(
            'SELECT COUNT(*) AS total FROM all_users WHERE username = ?',
            [strtoupper($username)],
        );

        return (int) ($row->total ?? 0) > 0;
    }

    protected static function createUser(string $username, string $password): void
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_$#]*$/', $username)) {
            throw new RuntimeException('DB_USERNAME in .env.testing is not valid for auto-provisioning.');
        }

        $oracleUser = strtoupper($username);
        $quotedPassword = self::quoteOraclePassword($password);

        DB::connection('oracle_dba')->unprepared(
            'CREATE USER '.$oracleUser.' IDENTIFIED BY '.$quotedPassword
        );

        DB::connection('oracle_dba')->unprepared(
            'GRANT CONNECT, RESOURCE TO '.$oracleUser
        );

        DB::connection('oracle_dba')->unprepared(
            'GRANT UNLIMITED TABLESPACE TO '.$oracleUser
        );
    }

    protected static function quoteOraclePassword(string $password): string
    {
        return '"'.str_replace('"', '""', $password).'"';
    }
}
