<?php

namespace App\Services;

use App\Models\MangaSource;
use App\Models\Chapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MangaSourceService
{
    protected MangaSource $source;
    protected array $config;

    public function __construct(MangaSource $source)
    {
        $this->source = $source;
        $this->config = $source->config;
    }

    public function scrapeChapters(): Collection
    {
        try {
            $response = Http::timeout($this->source->getConfig('timeout', 30))
                ->get($this->source->base_url);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch chapters: HTTP {$response->status()}");
            }

            $html = $response->body();
            $chapters = $this->parseChapters($html);

            return $this->saveChapters($chapters);
        } catch (\Exception $e) {
            Log::error("Scraping failed for {$this->source->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function parseChapters(string $html): array
    {
        // This is a placeholder for the actual parsing logic
        // In a real implementation, we would use a DOM parser like DomCrawler
        return [
            [
                'title' => 'Chapter 1',
                'chapter_number' => 1.0,
                'url' => $this->source->base_url . '/chapter-1',
            ],
            // More chapters would be parsed here
        ];
    }

    protected function saveChapters(array $chapters): Collection
    {
        return collect($chapters)->map(function ($chapterData) {
            return Chapter::updateOrCreate(
                ['url' => $chapterData['url'], 'manga_source_id' => $this->source->id],
                [
                    'title' => $chapterData['title'],
                    'chapter_number' => $chapterData['chapter_number'],
                    'status' => 'pending',
                ]
            );
        });
    }

    public function scrapeChapterContent(Chapter $chapter): void
    {
        try {
            $response = Http::timeout($this->source->getConfig('timeout', 30))
                ->get($chapter->url);

            if (!$response->successful()) {
                $this->markChapterAsFailed($chapter, "HTTP {$response->status()}");
                return;
            }

            $content = $this->parseChapterContent($response->body());
            $this->updateChapterContent($chapter, $content);
        } catch (\Exception $e) {
            $this->markChapterAsFailed($chapter, $e->getMessage());
            Log::error("Content scraping failed for chapter {$chapter->id}: {$e->getMessage()}");
        }
    }

    protected function parseChapterContent(string $html): array
    {
        // This is a placeholder for the actual content parsing logic
        return [
            'images' => [
                'https://example.com/page1.jpg',
                'https://example.com/page2.jpg',
            ],
        ];
    }

    protected function updateChapterContent(Chapter $chapter, array $content): void
    {
        $chapter->update([
            'content' => $content,
            'status' => 'processed',
            'metadata' => array_merge($chapter->metadata ?? [], [
                'processed_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    protected function markChapterAsFailed(Chapter $chapter, string $error): void
    {
        $chapter->update([
            'status' => 'failed',
            'metadata' => array_merge($chapter->metadata ?? [], [
                'error' => $error,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}