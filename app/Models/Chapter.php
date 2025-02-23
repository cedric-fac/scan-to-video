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
        'progress',
        'estimated_time',
        'error_message'
    ];

    protected $casts = [
        'chapter_number' => 'float',
        'content' => 'array',
        'metadata' => 'array',
        'progress' => 'integer',
        'estimated_time' => 'integer'
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

    public function getProgress(): int
    {
        return $this->progress ?? 0;
    }

    public function getEstimatedTime(): int
    {
        return $this->estimated_time ?? 0;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function updateProgress(int $progress, ?int $estimatedTime = null): void
    {
        $this->progress = $progress;
        if ($estimatedTime !== null) {
            $this->estimated_time = $estimatedTime;
        }
        $this->save();
    }

    public function setError(string $message): void
    {
        $this->status = 'failed';
        $this->error_message = $message;
        $this->save();
    }
}