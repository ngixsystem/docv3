<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'description', 'head_id'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public static function generateCode(string $name): string
    {
        $base = mb_strtoupper(mb_substr(preg_replace('/\s+/u', '', $name), 0, 4));

        return $base ?: 'DEPT';
    }
}
