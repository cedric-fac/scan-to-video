<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\MangaSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        return [
            'manga_source_id' => MangaSource::factory(),
            'title' => fake()->sentence(),
            'chapter_number' => fake()->randomFloat(1, 1, 1000),
            'url' => fake()->unique()->url(),
            'status' => fake()->randomElement(['pending', 'processed', 'failed']),
            'content' => [
                'images' => [
                    fake()->imageUrl(),
                    fake()->imageUrl(),
                ],
            ],
            'metadata' => [
                'downloaded_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
                'processing_time' => fake()->numberBetween(1, 60),
            ],
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'content' => null,
            'metadata' => null,
        ]);
    }

    public function processed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'metadata' => [
                'error' => fake()->sentence(),
                'failed_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}