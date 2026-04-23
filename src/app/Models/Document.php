<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'number', 'type', 'subject', 'description',
        'sender_id', 'recipient_id', 'recipient_group_id', 'sender_org', 'recipient_org',
        'executor_id', 'created_by', 'status', 'doc_date', 'deadline', 'tags',
    ];

    protected $casts = [
        'doc_date' => 'date',
        'deadline' => 'date',
    ];

    public static array $typeNames = [
        'incoming' => 'Входящее',
        'outgoing' => 'Исходящее',
        'memo' => 'Служебная записка',
        'internal' => 'Внутренний',
    ];

    public static array $typePrefixes = [
        'incoming' => 'ВХ',
        'outgoing' => 'ИСХ',
        'memo' => 'СЗ',
        'internal' => 'ВН',
    ];

    public static array $statusNames = [
        'draft' => 'Черновик',
        'registered' => 'Зарегистрирован',
        'review' => 'На рассмотрении',
        'approved' => 'Утвержден',
        'rejected' => 'Отклонен',
        'archive' => 'Архив',
    ];

    public static array $statusColors = [
        'draft' => '#6c757d',
        'registered' => '#0d6efd',
        'review' => '#fd7e14',
        'approved' => '#198754',
        'rejected' => '#dc3545',
        'archive' => '#6c757d',
    ];

    public static array $transitions = [
        'draft' => ['registered'],
        'registered' => ['review', 'archive'],
        'review' => ['approved', 'rejected'],
        'approved' => ['archive'],
        'rejected' => ['draft', 'archive'],
        'archive' => [],
    ];

    public function getTypeNameAttribute(): string
    {
        return self::$typeNames[$this->type] ?? $this->type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::$statusNames[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::$statusColors[$this->status] ?? '#6c757d';
    }

    public function getNextStatusesAttribute(): array
    {
        return self::$transitions[$this->status] ?? [];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function recipientGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'recipient_group_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class)->with('user')->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(DocumentStatusHistory::class)->with('user')->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['admin', 'clerk'])) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('created_by', $user->id)
                ->orWhere('sender_id', $user->id)
                ->orWhere('recipient_id', $user->id)
                ->orWhere('executor_id', $user->id)
                ->orWhereHas('recipientGroup.users', fn (Builder $users) => $users->where('users.id', $user->id));

            if ($user->hasRole('manager') && $user->department_id) {
                $inner->orWhereHas('sender', fn (Builder $users) => $users->where('department_id', $user->department_id))
                    ->orWhereHas('recipient', fn (Builder $users) => $users->where('department_id', $user->department_id))
                    ->orWhereHas('executor', fn (Builder $users) => $users->where('department_id', $user->department_id))
                    ->orWhereHas('createdBy', fn (Builder $users) => $users->where('department_id', $user->department_id));
            }
        });
    }

    public static function generateNumber(string $type): string
    {
        $prefix = self::$typePrefixes[$type] ?? 'ДОК';
        $year = date('Y');
        $count = self::where('type', $type)->count() + 1;

        return sprintf('%s-%03d/%s', $prefix, $count, $year);
    }
}
