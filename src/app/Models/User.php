<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'login',
        'email',
        'password',
        'department_id',
        'role',
        'position',
        'phone',
        'is_active',
        'must_change_password',
        'background_image',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'            => 'boolean',
        'must_change_password' => 'boolean',
    ];

    public static array $roleNames = [
        'admin' => 'Администратор',
        'ceo' => 'Генеральный директор',
        'manager' => 'Руководитель',
        'clerk' => 'Делопроизводитель',
        'employee' => 'Сотрудник',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }

    public function getRoleNameAttribute(): string
    {
        return self::$roleNames[$this->role] ?? $this->role;
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_substr($part, 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public function getShortNameAttribute(): string
    {
        $parts = explode(' ', $this->name);
        if (count($parts) >= 2) {
            return $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.';
        }

        return $this->name;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canEditDocument(Document $document): bool
    {
        return $this->isAdmin() || $document->created_by === $this->id;
    }

    public function canDeleteDocument(Document $document): bool
    {
        return $this->canEditDocument($document);
    }

    public function canManageStructure(): bool
    {
        return $this->isAdmin();
    }

    public function canCreateTasks(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    public function canRegisterDocuments(): bool
    {
        return $this->hasAnyRole(['admin', 'clerk', 'ceo']);
    }

    public function canApproveDocuments(): bool
    {
        return $this->hasAnyRole(['admin', 'manager', 'ceo']);
    }

    public function isDocumentRecipient(Document $document): bool
    {
        $document->loadMissing('recipients');
        if ($document->recipients->contains('id', $this->id)) {
            return true;
        }

        return $document->recipient_group_id
            ? $this->groups()->whereKey($document->recipient_group_id)->exists()
            : false;
    }

    public function isDocumentExecutor(Document $document): bool
    {
        if ($document->executor_id === $this->id) {
            return true;
        }

        $document->loadMissing('executors');

        return $document->executors->contains('id', $this->id);
    }

    public function canRecipientsViewDocument(Document $document): bool
    {
        return $document->status !== 'draft';
    }

    public function canChangeDocumentStatus(Document $document, string $status): bool
    {
        if ($status === 'registered') {
            return $this->canRegisterDocuments();
        }

        if (in_array($status, ['approved', 'rejected'], true)) {
            if ($status === 'approved' && !$document->allExecutorsCompleted()) {
                return false;
            }

            return $this->canApproveDocuments()
                || $this->isDocumentRecipient($document)
                || $this->isDocumentExecutor($document);
        }

        if (in_array($status, ['review', 'archive'], true)) {
            return $this->hasAnyRole(['admin', 'manager', 'clerk'])
                || $this->isDocumentRecipient($document)
                || $this->isDocumentExecutor($document);
        }

        return false;
    }

    public function allowedDocumentTypes(): array
    {
        return match ($this->role) {
            'employee' => ['memo'],
            default => array_keys(Document::$typeNames),
        };
    }

    public function canCreateDocumentType(string $type): bool
    {
        return in_array($type, $this->allowedDocumentTypes(), true);
    }

    public function canViewDocument(Document $document): bool
    {
        if ($this->hasAnyRole(['admin', 'clerk', 'ceo'])) {
            return true;
        }

        if (in_array($this->id, [
            $document->created_by,
            $document->sender_id,
            $document->executor_id,
        ], true)) {
            return true;
        }

        if ($this->canRecipientsViewDocument($document) && $this->isDocumentRecipient($document)) {
            return true;
        }

        if ($this->hasRole('manager') && $this->department_id) {
            $document->loadMissing([
                'sender:id,department_id',
                'recipients:id,department_id',
                'executor:id,department_id',
                'executors:id,department_id',
                'createdBy:id,department_id',
            ]);

            $visibleDepartmentIds = array_filter([
                optional($document->sender)->department_id,
                optional($document->executor)->department_id,
                optional($document->createdBy)->department_id,
            ]);

            if ($this->canRecipientsViewDocument($document)) {
                foreach ($document->recipients as $r) {
                    if ($r->department_id) {
                        $visibleDepartmentIds[] = $r->department_id;
                    }
                }
            }

            foreach ($document->executors as $executor) {
                if ($executor->department_id) {
                    $visibleDepartmentIds[] = $executor->department_id;
                }
            }

            return in_array($this->department_id, $visibleDepartmentIds, true);
        }

        return false;
    }

    public function canViewTask(Task $task): bool
    {
        if ($this->hasAnyRole(['admin', 'clerk', 'ceo'])) {
            return true;
        }

        if ($task->assignee_id === $this->id || $task->created_by === $this->id) {
            return true;
        }

        if ($task->document) {
            return $this->canViewDocument($task->document);
        }

        return false;
    }

    public function canChangeTask(Task $task): bool
    {
        return $this->hasAnyRole(['admin', 'manager', 'ceo']) || $task->assignee_id === $this->id;
    }
}
