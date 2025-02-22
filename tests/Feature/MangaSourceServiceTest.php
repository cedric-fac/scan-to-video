<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\MangaSource;
use App\Services\MangaSourceService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MangaSourceServiceTest extends TestCase
{
    use WithFaker;

    private MangaSource $source;
    private MangaSourceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = MangaSource::factory()->create([
            'config' => [
                'timeout' => 5,
            ],
        ]);
        $this->service = new MangaSourceService($this->source);
    }

    public function test_can_scrape_chapters(): void
    {
        Http::fake([
            $this->source->base_url => Http::response('<html><body>Test HTML</body></html>', 200),
        ]);

        $chapters = $this->service->scrapeChapters();

        $this->assertCount(1, $chapters);
        $this->assertDatabaseHas('chapters', [
            'manga_source_id' => $this->source->id,
            'title' => 'Chapter 1',
            'chapter_number' => 1.0,
            'status' => 'pending',
        ]);
    }

    public function test_scrape_chapters_handles_http_error(): void
    {
        Http::fake([
            $this->source->base_url => Http::response('', 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch chapters: HTTP 500');

        $this->service->scrapeChapters();
    }

    public function test_can_scrape_chapter_content(): void
    {
        $chapter = Chapter::factory()->pending()->create([
            'manga_source_id' => $this->source->id,
        ]);

        Http::fake([
            $chapter->url => Http::response('<html><body>Chapter content</body></html>', 200),
        ]);

        $this->service->scrapeChapterContent($chapter);

        $chapter->refresh();
        $this->assertTrue($chapter->isProcessed());
        $this->assertNotNull($chapter->content);
        $this->assertArrayHasKey('images', $chapter->content);
        $this->assertNotNull($chapter->metadata);
        $this->assertArrayHasKey('processed_at', $chapter->metadata);
    }

    public function test_scrape_chapter_content_handles_http_error(): void
    {
        $chapter = Chapter::factory()->pending()->create([
            'manga_source_id' => $this->source->id,
        ]);

        Http::fake([
            $chapter->url => Http::response('', 404),
        ]);

        $this->service->scrapeChapterContent($chapter);

        $chapter->refresh();
        $this->assertTrue($chapter->isFailed());
        $this->assertArrayHasKey('error', $chapter->metadata);
        $this->assertArrayHasKey('failed_at', $chapter->metadata);
        $this->assertStringContainsString('HTTP 404', $chapter->metadata['error']);
    }
}