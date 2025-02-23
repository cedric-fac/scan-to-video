<x-app-layout>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">{{ $source->name }}</h2>
                <div class="flex space-x-4">
                    <button onclick="document.getElementById('editSourceModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Edit Source
                    </button>
                    <form action="{{ route('sources.scrape', $source) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Scrape Chapters</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Source Details</h3>
                    <dl class="grid grid-cols-1 gap-2">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Base URL:</dt>
                            <dd class="text-sm text-gray-900">{{ $source->base_url }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Type:</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst($source->source_type) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Status:</dt>
                            <dd class="text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $source->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Statistics</h3>
                    <dl class="grid grid-cols-1 gap-2">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Total Chapters:</dt>
                            <dd class="text-sm text-gray-900">{{ $source->chapters->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Processed Chapters:</dt>
                            <dd class="text-sm text-gray-900">{{ $source->chapters->where('status', 'processed')->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Failed Chapters:</dt>
                            <dd class="text-sm text-gray-900">{{ $source->chapters->where('status', 'failed')->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Chapters</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($source->chapters->sortByDesc('chapter_number') as $chapter)
                                <tr id="chapter-{{ $chapter->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('chapters.show', $chapter) }}" class="hover:text-indigo-600">{{ $chapter->title }}</a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $chapter->chapter_number }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($chapter->status === 'processed') bg-green-100 text-green-800
                                            @elseif($chapter->status === 'failed') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800
                                            @endif">
                                            {{ ucfirst($chapter->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($chapter->status === 'processing')
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="progress-bar bg-blue-600 h-2.5 rounded-full" style="width: {{ $chapter->progress }}%"></div>
                                            </div>
                                            <span class="progress-text text-xs text-gray-500">{{ $chapter->progress }}%</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <a href="{{ route('chapters.show', $chapter) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                            @if($chapter->status === 'pending')
                                                <form action="{{ route('chapters.scrape-content', $chapter) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">Scrape</button>
                                                </form>
                                            @endif
                                            @if($chapter->status === 'processed')
                                                <form action="{{ route('chapters.generate-video', $chapter) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900">Generate</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Source Modal -->
    <div id="editSourceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Source</h3>
                <form action="{{ route('sources.update', $source) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                        <input type="text" name="name" id="name" value="{{ $source->name }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="base_url">Base URL</label>
                        <input type="url" name="base_url" id="base_url" value="{{ $source->base_url }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="source_type">Type</label>
                        <select name="source_type" id="source_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="manga" {{ $source->source_type === 'manga' ? 'selected' : '' }}>Manga</option>
                            <option value="manhwa" {{ $source->source_type === 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                            <option value="manhua" {{ $source->source_type === 'manhua' ? 'selected' : '' }}>Manhua</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ $source->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">Active</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mt-6">
                        <button type="button" onclick="document.getElementById('editSourceModal').classList.add('hidden')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>