<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'owner_id',
        'name',
        'key',
        'description',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function userMemberRole(User $user): ?string
    {
        if ($this->owner_id === $user->id) {
            return 'project_owner';
        }

        return $this->members()
            ->where('users.id', $user->id)
            ->first()
            ?->pivot
            ?->role;
    }

    public function userCanWrite(User $user): bool
    {
        if ($this->status === 'archived') {
            return false;
        }

        return in_array($this->userMemberRole($user), ['project_owner', 'scrum_master', 'developer'], true);
    }

    public function userCanManage(User $user): bool
    {
        return in_array($this->userMemberRole($user), ['project_owner', 'scrum_master'], true);
    }
}
