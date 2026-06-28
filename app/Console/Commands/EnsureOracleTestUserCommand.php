<?php

namespace App\Console\Commands;

use App\Support\OracleTestUserProvisioner;
use Illuminate\Console\Command;
use RuntimeException;

class EnsureOracleTestUserCommand extends Command
{
    protected $signature = 'oracle:ensure-test-user';

    protected $description = 'Create the Oracle test user from .env.testing (requires ORACLE_DBA_* credentials)';

    public function handle(): int
    {
        if (! is_file(base_path('.env.testing'))) {
            $this->error('Missing .env.testing. Copy .env.testing.example first.');

            return self::FAILURE;
        }

        if (! OracleTestUserProvisioner::hasDbaCredentials()) {
            $missing = OracleTestUserProvisioner::missingDbaEnvKeys();

            $this->error('DBA credentials are missing from .env.testing (not .env.testing.example).');

            if ($missing !== []) {
                $this->line('Add these lines to .env.testing:');
                $this->line('  ORACLE_DBA_USERNAME=system');
                $this->line('  ORACLE_DBA_PASSWORD=your_system_password');
                $this->line('Missing or empty: '.implode(', ', $missing));
            }

            return self::FAILURE;
        }

        try {
            OracleTestUserProvisioner::ensure();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $username = config('database.connections.oracle.username');
        $this->info('Oracle test user is ready: '.$username);
        $this->line('You can now run: php artisan test');

        return self::SUCCESS;
    }
}
