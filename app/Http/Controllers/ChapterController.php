<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Services\MangaSourceService;
use App\Services\VideoGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChapterController extends Controller
{
    public function index()
    {
        $chapters = Chapter::with('mangaSource')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('chapters.index', compact('chapters'));
    }

    public function show(Chapter $chapter)
    {
        $chapter->load('mangaSource');
        return view('chapters.show', compact('chapter'));
    }

    public function scrapeContent(Chapter $chapter)
    {
        try {
            $service = new MangaSourceService($chapter->mangaSource);
            $service->scrapeChapterContent($chapter);

            return redirect()->route('chapters.show', $chapter)
                ->with('success', 'Chapter content scraped successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to scrape content for chapter {$chapter->id}: {$e->getMessage()}");

            return redirect()->route('chapters.show', $chapter)
                ->with('error', 'Failed to scrape chapter content. Please try again later.');
        }
    }

    public function generateVideo(Chapter $chapter)
    {
        if (!$chapter->content || empty($chapter->content['images'])) {
            return redirect()->route('chapters.show', $chapter)
                ->with('error', 'Chapter content must be scraped before generating video.');
        }

        try {
            $service = new VideoGenerationService($chapter);
            $success = $service->generate();

            if ($success) {
                return redirect()->route('chapters.show', $chapter)
                    ->with('success', 'Video generation started successfully.');
            }

            return redirect()->route('chapters.show', $chapter)
                ->with('error', 'Failed to start video generation. Please try again later.');
        } catch (\Exception $e) {
            Log::error("Failed to generate video for chapter {$chapter->id}: {$e->getMessage()}");

            return redirect()->route('chapters.show', $chapter)
                ->with('error', 'Failed to generate video. Please try again later.');
        }
    }

    public function destroy(Chapter $chapter)
    {
        try {
            $chapter->delete();
            return redirect()->route('chapters.index')
                ->with('success', 'Chapter deleted successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to delete chapter {$chapter->id}: {$e->getMessage()}");

            return redirect()->route('chapters.index')
                ->with('error', 'Failed to delete chapter. Please try again later.');
        }
    }
}