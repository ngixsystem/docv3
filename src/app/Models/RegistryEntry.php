<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistryEntry extends Model
{
    protected $fillable = ['document_id', 'department_id', 'added_by', 'note', 'pinned'];

    protected $casts = ['pinned' => 'boolean'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
