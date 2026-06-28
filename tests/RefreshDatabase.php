<?php

namespace Tests;

use App\Support\OracleTestUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase as BaseRefreshDatabase;

trait RefreshDatabase
{
    use BaseRefreshDatabase;

    protected function beforeRefreshingDatabase()
    {
        if (! is_file(base_path('.env.testing'))) {
            $this->fail(
                'Missing .env.testing. Copy .env.testing.example, set ORACLE_DBA_* (or create the test user manually), then run tests again.'
            );
        }

        $devUsername = $this->envFileUsername(base_path('.env'));
        $testUsername = strtolower((string) config('database.connections.oracle.username'));

        if ($devUsername !== null && $testUsername === strtolower($devUsername)) {
            $this->fail(
                '.env.testing must use a different DB_USERNAME than .env so RefreshDatabase cannot wipe dev data.'
            );
        }

        try {
            OracleTestUserProvisioner::ensure();
        } catch (\RuntimeException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    protected function envFileUsername(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'DB_USERNAME=')) {
                return strtolower(trim(substr($line, strlen('DB_USERNAME=')), " \t\"'"));
            }
        }

        return null;
    }
}
