<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MangaSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'source_type',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}