<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\MangaSource;
use Tests\TestCase;

class ChapterTest extends TestCase
{
    public function test_can_create_chapter(): void
    {
        $chapter = Chapter::factory()->create();

        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'title' => $chapter->title,
            'chapter_number' => $chapter->chapter_number,
            'url' => $chapter->url,
            'status' => $chapter->status,
        ]);
    }

    public function test_can_create_pending_chapter(): void
    {
        $chapter = Chapter::factory()->pending()->create();

        $this->assertTrue($chapter->isPending());
        $this->assertNull($chapter->content);
        $this->assertNull($chapter->metadata);
    }

    public function test_can_create_processed_chapter(): void
    {
        $chapter = Chapter::factory()->processed()->create();

        $this->assertTrue($chapter->isProcessed());
        $this->assertNotNull($chapter->content);
        $this->assertNotNull($chapter->metadata);
    }

    public function test_can_create_failed_chapter(): void
    {
        $chapter = Chapter::factory()->failed()->create();

        $this->assertTrue($chapter->isFailed());
        $this->assertArrayHasKey('error', $chapter->metadata);
        $this->assertArrayHasKey('failed_at', $chapter->metadata);
    }

    public function test_belongs_to_manga_source(): void
    {
        $mangaSource = MangaSource::factory()->create();
        $chapter = Chapter::factory()
            ->for($mangaSource)
            ->create();

        $this->assertEquals($mangaSource->id, $chapter->mangaSource->id);
    }
}