<?php

namespace Tests\Unit;

use App\Support\SqlDialect;
use PHPUnit\Framework\TestCase;

class SqlDialectTest extends TestCase
{
    public function test_decode_json_from_string(): void
    {
        $decoded = SqlDialect::decodeJson('{"status":"done"}');

        $this->assertSame(['status' => 'done'], $decoded);
    }

    public function test_decode_json_returns_null_for_empty_values(): void
    {
        $this->assertNull(SqlDialect::decodeJson(null));
        $this->assertNull(SqlDialect::decodeJson(''));
        $this->assertNull(SqlDialect::decodeJson('   '));
    }

    public function test_clob_to_string_handles_strings_and_objects(): void
    {
        $this->assertSame('hello', SqlDialect::clobToString('hello'));
        $this->assertNull(SqlDialect::clobToString(null));

        $lob = new class
        {
            public function load(): string
            {
                return 'loaded';
            }
        };

        $this->assertSame('loaded', SqlDialect::clobToString($lob));
    }

    public function test_normalize_activity_decodes_json_fields(): void
    {
        $activity = (object) [
            'old_values' => '{"status":"backlog"}',
            'new_values' => '{"status":"done"}',
        ];

        SqlDialect::normalizeActivity($activity);

        $this->assertSame(['status' => 'backlog'], $activity->old_values);
        $this->assertSame(['status' => 'done'], $activity->new_values);
    }

    public function test_normalize_group_concat_trims_empty_values(): void
    {
        $this->assertSame('', SqlDialect::normalizeGroupConcat(null));
        $this->assertSame('', SqlDialect::normalizeGroupConcat(' '));
        $this->assertSame('a,b', SqlDialect::normalizeGroupConcat(' a,b '));
    }

    public function test_dual_from_uses_oracle_dual_table(): void
    {
        $this->assertSame(' FROM DUAL', SqlDialect::dualFrom());
    }

    public function test_apply_limit_uses_rownum_wrapper(): void
    {
        $sql = 'SELECT * FROM users ORDER BY id';

        $limited = SqlDialect::applyLimit($sql, 5);

        $this->assertStringContainsString('WHERE ROWNUM <= 5', $limited);
        $this->assertStringContainsString($sql, $limited);

        $paginated = SqlDialect::applyLimit($sql, 10, 20);

        $this->assertStringContainsString('oracle_rownum > 20', $paginated);
        $this->assertStringContainsString('ROWNUM <= 30', $paginated);
    }

    public function test_group_concat_uses_listagg(): void
    {
        $this->assertStringContainsString('LISTAGG(t.id', SqlDialect::groupConcat('t.id'));
    }

    public function test_max_issue_number_sql_uses_oracle_functions(): void
    {
        $sql = SqlDialect::maxIssueNumberSql();

        $this->assertStringContainsString('TO_NUMBER(SUBSTR', $sql);
        $this->assertStringContainsString('LENGTH(?)', $sql);
    }
}
