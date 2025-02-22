<?php

namespace Database\Factories;

use App\Models\MangaSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class MangaSourceFactory extends Factory
{
    protected $model = MangaSource::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'base_url' => fake()->url(),
            'source_type' => fake()->randomElement(['webtoon', 'manga', 'manhwa']),
            'is_active' => fake()->boolean(),
            'config' => [
                'selectors' => [
                    'chapter_list' => '.chapter-list',
                    'chapter_title' => '.chapter-title',
                    'chapter_content' => '.chapter-content',
                ],
                'rate_limit' => fake()->numberBetween(1, 10),
                'timeout' => fake()->numberBetween(10, 30),
            ],
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}