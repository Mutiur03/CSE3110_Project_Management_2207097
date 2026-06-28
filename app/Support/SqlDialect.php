<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SqlDialect
{
    public static function dualFrom(): string
    {
        return ' FROM DUAL';
    }

    public static function applyLimit(string $sql, int $limit, int $offset = 0): string
    {
        $sql = rtrim($sql);
        $maxRow = $offset + $limit;

        if ($offset > 0) {
            return "SELECT * FROM (
                SELECT oracle_limited.*, ROWNUM AS oracle_rownum FROM (
                    {$sql}
                ) oracle_limited WHERE ROWNUM <= {$maxRow}
            ) WHERE oracle_rownum > {$offset}";
        }

        return "SELECT * FROM (
            {$sql}
        ) WHERE ROWNUM <= {$limit}";
    }

    public static function groupConcat(string $column, string $alias = 'team_ids', string $separator = ','): string
    {
        return "LISTAGG({$column}, '{$separator}') WITHIN GROUP (ORDER BY {$column}) AS {$alias}";
    }

    public static function maxIssueNumberSql(): string
    {
        return 'SELECT MAX(TO_NUMBER(SUBSTR(i.key, LENGTH(?) + 2))) AS last_number
                FROM issues i
                WHERE i.project_id = ? AND i.key LIKE ?';
    }

    public static function clobToString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_object($value)) {
            if (method_exists($value, 'load')) {
                return $value->load();
            }

            if (method_exists($value, 'read')) {
                $size = method_exists($value, 'size') ? $value->size() : null;

                return $size !== null ? $value->read($size) : $value->read();
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
        }

        if (is_resource($value)) {
            $contents = stream_get_contents($value);

            return $contents === false ? null : $contents;
        }

        return (string) $value;
    }

    public static function decodeJson(mixed $value): ?array
    {
        $string = self::clobToString($value);

        if ($string === null || trim($string) === '') {
            return null;
        }

        $decoded = json_decode($string, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function stringifyProperties(object $row, array $properties): object
    {
        foreach ($properties as $property) {
            if (! property_exists($row, $property)) {
                continue;
            }

            $row->{$property} = self::clobToString($row->{$property});
        }

        return $row;
    }

    public static function normalizeProject(?object $project): ?object
    {
        if ($project === null) {
            return null;
        }

        return self::stringifyProperties($project, ['description']);
    }

    public static function normalizeSprint(?object $sprint): ?object
    {
        if ($sprint === null) {
            return null;
        }

        return self::stringifyProperties($sprint, ['goal']);
    }

    public static function normalizeTeam(?object $team): ?object
    {
        if ($team === null) {
            return null;
        }

        return self::stringifyProperties($team, ['description']);
    }

    public static function normalizeIssue(?object $issue): ?object
    {
        if ($issue === null) {
            return null;
        }

        return self::stringifyProperties($issue, [
            'description',
            'steps_to_reproduce',
            'expected_result',
            'actual_result',
        ]);
    }

    public static function normalizeComment(object $comment): object
    {
        return self::stringifyProperties($comment, ['body']);
    }

    public static function normalizeActivity(object $activity): object
    {
        $activity->old_values = self::decodeJson($activity->old_values ?? null);
        $activity->new_values = self::decodeJson($activity->new_values ?? null);

        return $activity;
    }

    public static function normalizeGroupConcat(mixed $value): string
    {
        $text = trim(self::clobToString($value) ?? '');

        return $text === '' ? '' : $text;
    }

    public static function normalizeMemberWithTeamIds(object $member): object
    {
        if (property_exists($member, 'team_ids')) {
            $member->team_ids = self::normalizeGroupConcat($member->team_ids);
        }

        return $member;
    }

    public static function withCreatedAtHuman(object $row): object
    {
        if (property_exists($row, 'created_at')) {
            $row->created_at_human = $row->created_at
                ? Carbon::parse($row->created_at)->diffForHumans()
                : null;
        }

        return $row;
    }

    public static function stripPaginationColumns(object $row): object
    {
        unset($row->oracle_rownum, $row->rnum);

        return $row;
    }

    /** @param array<int, object> $rows */
    public static function mapIssues(array $rows): Collection
    {
        return collect($rows)->map(fn ($row) => self::normalizeIssue($row));
    }

    /** @param array<int, object> $rows */
    public static function mapProjects(array $rows): Collection
    {
        return collect($rows)->map(fn ($row) => self::normalizeProject($row));
    }

    /** @param array<int, object> $rows */
    public static function mapSprints(array $rows): Collection
    {
        return collect($rows)->map(fn ($row) => self::normalizeSprint($row));
    }

    /** @param array<int, object> $rows */
    public static function mapTeams(array $rows): Collection
    {
        return collect($rows)->map(fn ($row) => self::normalizeTeam($row));
    }

    /** @param array<int, object> $rows */
    public static function mapComments(array $rows): Collection
    {
        return collect($rows)
            ->map(fn ($row) => self::normalizeComment($row))
            ->map(fn ($row) => self::withCreatedAtHuman($row));
    }

    /** @param array<int, object> $rows */
    public static function mapActivities(array $rows): Collection
    {
        return collect($rows)
            ->map(fn ($row) => self::stripPaginationColumns($row))
            ->map(fn ($row) => self::normalizeActivity($row))
            ->map(fn ($row) => self::withCreatedAtHuman($row));
    }

    /** @param array<int, object> $rows */
    public static function mapMembersWithTeamIds(array $rows): Collection
    {
        return collect($rows)->map(fn ($row) => self::normalizeMemberWithTeamIds($row));
    }
}
