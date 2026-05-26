<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'project_id',
        'team_id',
        'sprint_id',
        'reporter_id',
        'assignee_id',
        'parent_issue_id',
        'key',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'story_points',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function parentIssue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'parent_issue_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
