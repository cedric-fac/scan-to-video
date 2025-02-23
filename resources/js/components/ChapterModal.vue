<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <div
        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-headline"
      >
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
                Chapter Details
              </h3>
              <div class="mt-4">
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700">Title</label>
                  <input
                    type="text"
                    v-model="editedChapter.title"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700">Source</label>
                  <input
                    type="text"
                    v-model="editedChapter.source"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <select
                    v-model="editedChapter.status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                  </select>
                </div>
                <div v-if="editedChapter.video_url" class="mb-4">
                  <label class="block text-sm font-medium text-gray-700">Video Preview</label>
                  <video
                    :src="editedChapter.video_url"
                    controls
                    class="mt-1 w-full rounded-md"
                  ></video>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
            @click="saveChanges"
          >
            Save Changes
          </button>
          <button
            type="button"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            @click="closeModal"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  chapter: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update:isOpen', 'save']);

const editedChapter = ref({ ...props.chapter });

watch(() => props.chapter, (newChapter) => {
  editedChapter.value = { ...newChapter };
});

function closeModal() {
  emit('update:isOpen', false);
}

function saveChanges() {
  emit('save', editedChapter.value);
  closeModal();
}
</script>