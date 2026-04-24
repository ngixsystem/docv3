<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'assignee_id', 'created_by',
        'document_id', 'status', 'priority', 'deadline',
    ];

    protected $casts = ['deadline' => 'date'];

    public static array $statusNames = [
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'paused' => 'Приостановлена',
        'done' => 'Выполнена',
    ];

    public static array $priorityNames = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочный',
    ];

    public static array $priorityColors = [
        'low' => '#6c757d',
        'medium' => '#0d6efd',
        'high' => '#fd7e14',
        'urgent' => '#dc3545',
    ];

    public function getStatusNameAttribute(): string
    {
        return self::$statusNames[$this->status] ?? $this->status;
    }

    public function getPriorityNameAttribute(): string
    {
        return self::$priorityNames[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        return self::$priorityColors[$this->priority] ?? '#6c757d';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'done';
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->with('user')->latest();
    }

    public function taskFiles(): HasMany
    {
        return $this->hasMany(TaskFile::class)->with('uploader')->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class)->with('user')->latest();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['admin', 'clerk'])) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('assignee_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('document', fn (Builder $documents) => $documents->visibleTo($user));
        });
    }
}
