import { ref, computed, watch } from 'vue';

export function useChapterFilters(chapters, itemsPerPage = 20) {
  const filters = ref({
    searchTerm: '',
    statusFilter: 'all',
    sortBy: 'newest',
    currentPage: 1,
    chapterNumber: '',
    progressFilter: 'all'
  });

  const debouncedSearchTerm = ref('');
  const debouncedChapterNumber = ref('');
  let searchDebounceTimer = null;
  let chapterDebounceTimer = null;

  // Watch for search term changes and debounce
  watch(() => filters.value.searchTerm, (newValue) => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      debouncedSearchTerm.value = newValue;
      filters.value.currentPage = 1; // Reset to first page on search
    }, 300);
  });

  // Watch for chapter number changes and debounce
  watch(() => filters.value.chapterNumber, (newValue) => {
    if (chapterDebounceTimer) clearTimeout(chapterDebounceTimer);
    chapterDebounceTimer = setTimeout(() => {
      debouncedChapterNumber.value = newValue;
      filters.value.currentPage = 1;
    }, 300);
  });

  const updateFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
  };

  const filteredChapters = computed(() => {
    let result = [...chapters];

    // Apply search filter using debounced term
    if (debouncedSearchTerm.value) {
      const term = debouncedSearchTerm.value.toLowerCase();
      result = result.filter(chapter =>
        chapter.title.toLowerCase().includes(term) ||
        chapter.source?.toLowerCase().includes(term)
      );
    }

    // Apply chapter number filter
    if (debouncedChapterNumber.value) {
      const number = parseFloat(debouncedChapterNumber.value);
      if (!isNaN(number)) {
        result = result.filter(chapter =>
          chapter.chapter_number === number
        );
      }
    }

    // Apply status filter
    if (filters.value.statusFilter !== 'all') {
      result = result.filter(chapter =>
        chapter.status.toLowerCase() === filters.value.statusFilter
      );
    }

    // Apply progress filter
    if (filters.value.progressFilter !== 'all') {
      switch (filters.value.progressFilter) {
        case 'completed':
          result = result.filter(chapter => chapter.progress === 100);
          break;
        case 'in-progress':
          result = result.filter(chapter => chapter.progress > 0 && chapter.progress < 100);
          break;
        case 'not-started':
          result = result.filter(chapter => chapter.progress === 0);
          break;
      }
    }

    // Apply sorting
    result.sort((a, b) => {
      switch (filters.value.sortBy) {
        case 'newest':
          return new Date(b.date) - new Date(a.date);
        case 'oldest':
          return new Date(a.date) - new Date(b.date);
        case 'title':
          return a.title.localeCompare(b.title);
        case 'source':
          return (a.source || '').localeCompare(b.source || '');
        case 'chapter':
          return a.chapter_number - b.chapter_number;
        case 'progress':
          return b.progress - a.progress;
        default:
          return 0;
      }
    });

    return result;
  });

  // Pagination computed properties
  const totalPages = computed(() => 
    Math.ceil(filteredChapters.value.length / itemsPerPage)
  );

  const paginatedChapters = computed(() => {
    const startIndex = (filters.value.currentPage - 1) * itemsPerPage;
    return filteredChapters.value.slice(startIndex, startIndex + itemsPerPage);
  });

  const goToPage = (page) => {
    filters.value.currentPage = Math.max(1, Math.min(page, totalPages.value));
  };

  return {
    filters,
    updateFilters,
    filteredChapters,
    paginatedChapters,
    totalPages,
    goToPage
  };
}