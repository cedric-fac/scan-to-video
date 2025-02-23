<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MangaSourceController;
use App\Http\Controllers\ChapterController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::resource('sources', MangaSourceController::class);
Route::post('sources/{source}/scrape', [MangaSourceController::class, 'scrape'])->name('sources.scrape');

Route::resource('chapters', ChapterController::class);
Route::post('chapters/{chapter}/scrape-content', [ChapterController::class, 'scrapeContent'])->name('chapters.scrape-content');
Route::post('chapters/{chapter}/generate-video', [ChapterController::class, 'generateVideo'])->name('chapters.generate-video');
