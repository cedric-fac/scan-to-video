<?php

namespace App\Http\Controllers;

use App\Models\MangaSource;
use App\Services\MangaSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MangaSourceController extends Controller
{
    public function index()
    {
        $sources = MangaSource::with(['chapters' => function ($query) {
            $query->orderBy('chapter_number', 'desc');
        }])->get();

        return view('sources.index', compact('sources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:255',
            'source_type' => 'required|string|in:manga,manhwa,manhua',
            'config' => 'nullable|array'
        ]);

        $source = MangaSource::create($validated);

        return redirect()->route('sources.show', $source)
            ->with('success', 'Manga source created successfully.');
    }

    public function show(MangaSource $source)
    {
        $source->load(['chapters' => function ($query) {
            $query->orderBy('chapter_number', 'desc');
        }]);

        return view('sources.show', compact('source'));
    }

    public function update(Request $request, MangaSource $source)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:255',
            'source_type' => 'required|string|in:manga,manhwa,manhua',
            'is_active' => 'boolean',
            'config' => 'nullable|array'
        ]);

        $source->update($validated);

        return redirect()->route('sources.show', $source)
            ->with('success', 'Manga source updated successfully.');
    }

    public function destroy(MangaSource $source)
    {
        $source->delete();

        return redirect()->route('sources.index')
            ->with('success', 'Manga source deleted successfully.');
    }

    public function scrape(MangaSource $source)
    {
        try {
            $service = new MangaSourceService($source);
            $chapters = $service->scrapeChapters();

            return redirect()->route('sources.show', $source)
                ->with('success', "Successfully scraped {$chapters->count()} chapters.");
        } catch (\Exception $e) {
            Log::error("Failed to scrape chapters for source {$source->id}: {$e->getMessage()}");
            
            return redirect()->route('sources.show', $source)
                ->with('error', 'Failed to scrape chapters. Please try again later.');
        }
    }
}