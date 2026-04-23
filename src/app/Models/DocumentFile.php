<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFile extends Model
{
    protected $fillable = [
        'document_id', 'original_name', 'stored_name',
        'mime_type', 'file_size', 'path', 'uploaded_by',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    public function getFormattedSizeAttribute(): string
    {
        $size = $this->file_size;
        if ($size >= 1048576) return round($size / 1048576, 1) . ' МБ';
        if ($size >= 1024)    return round($size / 1024, 1) . ' КБ';
        return $size . ' Б';
    }

    public function getIconClassAttribute(): string
    {
        return match (strtolower($this->extension)) {
            'pdf'        => 'icon-pdf',
            'doc', 'docx'=> 'icon-word',
            'xls', 'xlsx'=> 'icon-excel',
            'jpg', 'jpeg', 'png' => 'icon-image',
            default      => 'icon-file',
        };
    }
}
