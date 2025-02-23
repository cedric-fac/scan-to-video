<template>
  <div class="mb-4 flex space-x-4">
    <input
      id="search"
      v-model="searchTerm"
      type="text"
      class="rounded-lg border p-2"
      aria-label="Search chapters"
      placeholder="Search chapters..."
      @input="emitFilters"
    />
    <select
      id="status-filter"
      v-model="statusFilter"
      class="rounded-lg border p-2"
      aria-label="Filter by status"
      @change="emitFilters"
    >
      <option value="all">All Status</option>
      <option value="pending">Pending</option>
      <option value="processing">Processing</option>
      <option value="completed">Completed</option>
    </select>
    <select
      id="sort-by"
      v-model="sortBy"
      class="rounded-lg border p-2"
      aria-label="Sort chapters"
      @change="emitFilters"
    >
      <option value="newest">Newest First</option>
      <option value="oldest">Oldest First</option>
      <option value="title">Title</option>
      <option value="source">Source</option>
    </select>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const searchTerm = ref('');
const statusFilter = ref('all');
const sortBy = ref('newest');

const emit = defineEmits(['filter-change']);

function emitFilters() {
  emit('filter-change', {
    searchTerm: searchTerm.value,
    statusFilter: statusFilter.value,
    sortBy: sortBy.value
  });
}
</script>