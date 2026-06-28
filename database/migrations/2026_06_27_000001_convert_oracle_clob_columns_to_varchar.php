<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array<string, int>> */
    private array $columns = [
        'projects' => [
            'description' => 1000,
        ],
        'teams' => [
            'description' => 1000,
        ],
        'sprints' => [
            'goal' => 1000,
        ],
        'issues' => [
            'description' => 4000,
            'steps_to_reproduce' => 4000,
            'expected_result' => 4000,
            'actual_result' => 4000,
        ],
        'comments' => [
            'body' => 4000,
        ],
        'activity_logs' => [
            'old_values' => 4000,
            'new_values' => 4000,
        ],
        'notifications' => [
            'data' => 4000,
        ],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'oracle') {
            return;
        }

        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column => $length) {
                $this->convertClobToVarchar($table, $column, $length);
            }
        }
    }

    public function down(): void
    {
        // Irreversible without data loss on Oracle.
    }

    private function convertClobToVarchar(string $table, string $column, int $length): void
    {
        $tableName = strtoupper($table);
        $columnName = strtoupper($column);
        $tmpColumn = "{$columnName}_TMP";

        $exists = DB::selectOne(
            'SELECT COUNT(*) AS total
             FROM user_tab_columns
             WHERE table_name = ? AND column_name = ?',
            [$tableName, $columnName],
        );

        if ((int) ($exists->total ?? 0) === 0) {
            return;
        }

        $dataType = DB::selectOne(
            'SELECT data_type
             FROM user_tab_columns
             WHERE table_name = ? AND column_name = ?',
            [$tableName, $columnName],
        );

        if (strtoupper((string) ($dataType->data_type ?? '')) !== 'CLOB') {
            return;
        }

        DB::statement("ALTER TABLE {$tableName} ADD ({$tmpColumn} VARCHAR2({$length}))");
        DB::statement("UPDATE {$tableName} SET {$tmpColumn} = DBMS_LOB.SUBSTR({$columnName}, {$length}, 1)");
        DB::statement("ALTER TABLE {$tableName} DROP COLUMN {$columnName}");
        DB::statement("ALTER TABLE {$tableName} RENAME COLUMN {$tmpColumn} TO {$columnName}");
    }
};
