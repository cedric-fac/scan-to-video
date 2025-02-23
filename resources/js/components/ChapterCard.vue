<template>
  <div
    class="chapter-card p-4 border rounded-lg shadow transition-opacity duration-300 cursor-pointer"
    role="gridcell"
    tabindex="0"
    @click="openModal"
    @keydown.enter="openModal"
    @keydown.space="openModal"
  >
    <h3 class="text-lg font-semibold">{{ chapter.title }}</h3>
    <p class="text-gray-700">{{ chapter.source }}</p>
    <p class="text-gray-600">{{ formatDate(chapter.date) }}</p>
    <span class="status-badge inline-block px-2 py-1 rounded text-sm" :class="statusClass">
      {{ chapter.status }}
    </span>

    <chapter-modal
      v-model:isOpen="isModalOpen"
      :chapter="chapter"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ChapterModal from './ChapterModal.vue';

const props = defineProps({
  chapter: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update:chapter']);

const isModalOpen = ref(false);

const statusClass = computed(() => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800'
  };
  return classes[props.chapter.status.toLowerCase()] || 'bg-gray-100 text-gray-800';
});

function formatDate(date) {
  return new Date(date).toLocaleDateString();
}

function openModal() {
  isModalOpen.value = true;
}

function handleSave(updatedChapter) {
  emit('update:chapter', updatedChapter);
}
</script>

<style scoped>
.chapter-card {
  opacity: 1;
  transition: opacity 0.3s ease-in-out;
}

.chapter-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>