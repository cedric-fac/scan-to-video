<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'manga_source_id',
        'title',
        'chapter_number',
        'url',
        'status',
        'content',
        'metadata',
    ];

    protected $casts = [
        'chapter_number' => 'float',
        'content' => 'array',
        'metadata' => 'array',
    ];

    public function mangaSource(): BelongsTo
    {
        return $this->belongsTo(MangaSource::class);
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}