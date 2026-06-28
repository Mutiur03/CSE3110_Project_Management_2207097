<?php

namespace App\Http\Controllers;

use App\Support\SqlDialect;

abstract class Controller
{
    protected function applyLimitSql(string $sql, int $limit, int $offset = 0): string
    {
        return SqlDialect::applyLimit($sql, $limit, $offset);
    }
}
