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
            'ppt', 'pptx'=> 'icon-powerpoint',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'icon-image',
            default      => 'icon-file',
        };
    }

    public function isImage(): bool
    {
        return in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }

    public function isPdf(): bool
    {
        return strtolower($this->extension) === 'pdf';
    }

    public function isText(): bool
    {
        return in_array(strtolower($this->extension), ['txt', 'csv', 'json', 'xml', 'log', 'md'], true);
    }

    public function isAudio(): bool
    {
        return in_array(strtolower($this->extension), ['mp3', 'wav', 'ogg', 'm4a'], true);
    }

    public function isVideo(): bool
    {
        return in_array(strtolower($this->extension), ['mp4', 'webm', 'ogg'], true);
    }

    public function isOfficeDocument(): bool
    {
        return in_array(strtolower($this->extension), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
    }
}
