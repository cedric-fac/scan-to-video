@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Video Player Container -->
        <div class="relative aspect-w-16 aspect-h-9" id="video-container">
            <video
                id="manga-video"
                class="w-full h-full object-contain"
                playsinline
            >
                <source src="{{ $chapter->video_url }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>

            <!-- Custom Controls -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 transition-opacity duration-300" id="video-controls">
                <div class="flex flex-col gap-2">
                    <!-- Progress Bar -->
                    <div class="relative h-1 bg-gray-600 rounded cursor-pointer" id="progress-bar">
                        <div class="absolute h-full bg-primary-500 rounded" id="progress-fill"></div>
                        <div class="absolute h-3 w-3 bg-primary-500 rounded-full -top-1 -ml-1.5 hidden" id="progress-handle"></div>
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center justify-between text-white">
                        <div class="flex items-center gap-4">
                            <button class="hover:text-primary-400 transition-colors" id="play-pause">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                            <div class="text-sm" id="time-display">0:00 / 0:00</div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button class="hover:text-primary-400 transition-colors" id="volume-toggle">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </button>
                            <button class="hover:text-primary-400 transition-colors" id="fullscreen-toggle">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chapter Information -->
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-2">{{ $chapter->title }}</h1>
            <div class="flex justify-between items-center">
                <div class="text-gray-600">
                    Chapter {{ $chapter->number }}
                </div>
                <div class="flex gap-2">
                    @if($previousChapter)
                        <a href="{{ route('chapters.show', $previousChapter) }}" class="btn btn-secondary">
                            Previous Chapter
                        </a>
                    @endif

                    @if($nextChapter)
                        <a href="{{ route('chapters.show', $nextChapter) }}" class="btn btn-primary">
                            Next Chapter
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    @vite('resources/css/video-player.css')
@endpush

@push('scripts')
    @vite('resources/js/video-progress.js')
@endpush