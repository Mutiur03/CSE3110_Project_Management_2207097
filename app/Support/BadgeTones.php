<?php

namespace App\Support;

class BadgeTones
{
    public const NEUTRAL = 'bg-muted text-muted-foreground';

    public const ACCENT = 'bg-accent/10 text-accent';

    public const DESTRUCTIVE = 'bg-destructive/10 text-destructive';

    public const WARNING = 'bg-amber-500/10 text-amber-900';

    /** @return array<string, string> */
    public static function issueType(): array
    {
        return [
            'epic' => self::NEUTRAL,
            'story' => self::NEUTRAL,
            'task' => self::NEUTRAL,
            'subtask' => self::NEUTRAL,
            'bug' => self::NEUTRAL,
        ];
    }

    /** Icon / text color for backlog type icons (no pill background). */
    /** @return array<string, string> */
    public static function issueTypeIcon(): array
    {
        return array_fill_keys(array_keys(self::issueType()), 'text-muted-foreground');
    }

    /** @return array<string, string> */
    public static function issueStatus(): array
    {
        return [
            'backlog' => self::NEUTRAL,
            'selected' => 'bg-sky-500/10 text-sky-800',
            'in_progress' => self::WARNING,
            'review' => 'bg-violet-500/10 text-violet-800',
            'done' => self::ACCENT,
        ];
    }

    /** @return array<string, string> */
    public static function issuePriority(): array
    {
        return [
            'low' => self::NEUTRAL,
            'medium' => self::NEUTRAL,
            'high' => self::WARNING,
            'urgent' => self::DESTRUCTIVE,
        ];
    }

    /** @return array<string, string> */
    public static function sprintStatus(): array
    {
        return [
            'planned' => self::NEUTRAL,
            'active' => self::ACCENT,
            'completed' => self::NEUTRAL,
        ];
    }

    /** @return array<string, string> */
    public static function projectStatus(): array
    {
        return [
            'active' => self::ACCENT,
            'archived' => self::WARNING,
        ];
    }

    /** @return array<string, string> */
    public static function severity(bool $critical = false): string
    {
        return $critical ? self::DESTRUCTIVE : self::NEUTRAL;
    }

    public static function storyPoints(): string
    {
        return self::NEUTRAL;
    }
}
