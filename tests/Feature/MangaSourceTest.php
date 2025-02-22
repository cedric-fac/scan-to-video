<?php

namespace Tests\Feature;

use App\Models\MangaSource;
use Tests\TestCase;

class MangaSourceTest extends TestCase
{
    public function test_can_create_manga_source(): void
    {
        $mangaSource = MangaSource::factory()->create();

        $this->assertDatabaseHas('manga_sources', [
            'id' => $mangaSource->id,
            'name' => $mangaSource->name,
            'base_url' => $mangaSource->base_url,
            'source_type' => $mangaSource->source_type,
        ]);
    }

    public function test_can_create_active_manga_source(): void
    {
        $mangaSource = MangaSource::factory()->active()->create();

        $this->assertTrue($mangaSource->isActive());
    }

    public function test_can_create_inactive_manga_source(): void
    {
        $mangaSource = MangaSource::factory()->inactive()->create();

        $this->assertFalse($mangaSource->isActive());
    }

    public function test_can_get_config_value(): void
    {
        $mangaSource = MangaSource::factory()->create();

        $this->assertIsInt($mangaSource->getConfig('rate_limit'));
        $this->assertIsInt($mangaSource->getConfig('timeout'));
        $this->assertEquals('.chapter-list', $mangaSource->getConfig('selectors.chapter_list'));
    }

    public function test_can_have_chapters(): void
    {
        $mangaSource = MangaSource::factory()
            ->has('chapters', 3)
            ->create();

        $this->assertCount(3, $mangaSource->chapters);
    }
}