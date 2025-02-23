@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Manga Sources</h2>
            <a href="{{ route('sources.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                Add New Source
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($sources as $source)
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium">{{ $source->name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $source->url }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('sources.edit', $source) }}" class="text-blue-500 hover:text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('sources.destroy', $source) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Are you sure you want to delete this source?')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Last Updated: {{ $source->updated_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No manga sources found. Add your first source to get started!</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection