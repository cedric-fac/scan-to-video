@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Manga Chapters</h2>
            <a href="{{ route('chapters.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                Add New Chapter
            </a>
        </div>

        <div class="mb-6 space-y-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" 
                           id="search" 
                           name="search" 
                           placeholder="Search by title or source..." 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                </div>
                <div class="flex gap-2">
                    <select id="status-filter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                        <option value="all">All Status</option>
                        <option value="processed">Processed</option>
                        <option value="pending">Processing</option>
                        <option value="failed">Failed</option>
                    </select>
                    <select id="sort-by" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="title">Title A-Z</option>
                        <option value="source">Source</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($chapters as $chapter)
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-sm p-6 transform transition duration-300 ease-in-out hover:scale-102 hover:shadow-lg" data-chapter-id="{{ $chapter->id }}">
                <div class="flex justify-between items-start">
                    <div class="flex-grow">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-lg font-medium truncate">{{ $chapter->title }}</h3>
                            <span class="status-badge inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full transition-all duration-300
                                {{ $chapter->isProcessed() ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '' }}
                                {{ $chapter->isPending() ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100 animate-pulse' : '' }}
                                {{ $chapter->isFailed() ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' : '' }}"
                            >
                                {{ $chapter->isProcessed() ? 'Processed' : '' }}
                                {{ $chapter->isPending() ? 'Processing' : '' }}
                                {{ $chapter->isFailed() ? 'Failed' : '' }}
                            </span>
                            @if($chapter->error_message)
                                <span class="error-message text-xs text-red-600 dark:text-red-400 ml-2 inline-flex items-center animate-bounce">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $chapter->error_message }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Source: {{ $chapter->manga_source->name }}</p>
                    </div>
                    <div class="flex space-x-2 ml-4">
                        <a href="{{ route('chapters.show', $chapter) }}" class="text-green-500 hover:text-green-600 transition duration-150 ease-in-out transform hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                        <a href="{{ route('chapters.edit', $chapter) }}" class="text-blue-500 hover:text-blue-600 transition duration-150 ease-in-out transform hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('chapters.destroy', $chapter) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600 transition duration-150 ease-in-out transform hover:scale-110" onclick="return confirm('Are you sure you want to delete this chapter?')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                @if($chapter->isPending())
                <div class="mt-4">
                    <div class="relative pt-1">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600 overflow-hidden">
                            <div class="progress-bar bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-in-out relative" style="width: {{ $chapter->progress }}%">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-blue-600 animate-shimmer"></div>
                            </div>
                        </div>
                        <div class="flex justify-between mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="progress-text font-medium">Progress: {{ $chapter->progress }}%</span>
                            <span class="estimated-time">{{ $chapter->estimated_time ? 'Est. Time: ' . $chapter->estimated_time . ' min' : '' }}</span>
                        </div>
                    </div>
                </div>
                @endif
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Last Updated: {{ $chapter->updated_at->diffForHumans() }}</span>
                    <a href="{{ route('chapters.generate-video', $chapter) }}" 
                       class="generate-button {{ $chapter->isPending() ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-500 hover:bg-indigo-600' }} 
                              text-white text-sm font-bold py-2 px-4 rounded transition duration-150 ease-in-out flex items-center transform hover:scale-105"
                       {{ $chapter->isPending() ? 'disabled' : '' }}>
                        @if($chapter->isPending())
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        @else
                            Generate Video
                        @endif
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="mt-2 text-gray-500 dark:text-gray-400">No chapters found. Add your first chapter to get started!</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}

.hover\:scale-102:hover {
    transform: scale(1.02);
}
</style>
@endsection