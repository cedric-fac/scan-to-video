<template>
  <div>
    <filter-bar @filter-change="updateFilters" />

    <div v-if="filteredChapters.length === 0" class="col-span-full text-center py-8">
      No chapters found matching your criteria
    </div>

    <div
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
      role="grid"
      aria-label="Chapters list"
    >
      <chapter-card
        v-for="chapter in filteredChapters"
        :key="chapter.id"
        :chapter="chapter"
      />
    </div>
  </div>
</template>

<script setup>
import FilterBar from './FilterBar.vue';
import ChapterCard from './ChapterCard.vue';
import { useChapterFilters } from '../composables/useChapterFilters';

// Props for chapter data
const props = defineProps({
  chapters: {
    type: Array,
    required: true
  }
});

// Use the composable for filtering logic
const { filters, updateFilters, filteredChapters } = useChapterFilters(props.chapters);
</script>