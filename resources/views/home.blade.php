@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <div class="max-w-3xl mx-auto text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Welcome to Manhwa Video Creator</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Create stunning video content from your favorite manga sources</p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('sources.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Manage Sources
                </a>
                <a href="{{ route('chapters.index') }}" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    View Chapters
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12">
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h3 class="text-xl font-semibold mb-4">Quick Start Guide</h3>
                <ol class="list-decimal list-inside space-y-3 text-gray-600 dark:text-gray-400">
                    <li>Add a new manga source with its URL</li>
                    <li>Scrape chapters from the source</li>
                    <li>Select chapters to generate videos</li>
                    <li>Customize video settings if needed</li>
                    <li>Generate and download your videos</li>
                </ol>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h3 class="text-xl font-semibold mb-4">Features</h3>
                <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Automatic chapter scraping
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Customizable video generation
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Batch processing support
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Real-time progress tracking
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection