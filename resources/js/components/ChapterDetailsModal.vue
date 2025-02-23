<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" @click="close">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="w-full">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                  {{ chapter.title }}
                </h3>
                <button @click="close" class="text-gray-400 hover:text-gray-500">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <div class="space-y-4">
                <VideoPreview :video-url="chapter.video_url" />
                <div class="mt-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Chapter Details</h4>
                  <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Source</dt>
                      <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ chapter.manga_source.name }}</dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                      <dd class="mt-1 text-sm">
                        <span :class="{
                          'text-green-600 dark:text-green-400': chapter.isProcessed,
                          'text-yellow-600 dark:text-yellow-400': chapter.isPending,
                          'text-red-600 dark:text-red-400': chapter.isFailed
                        }">
                          {{ chapter.status }}
                        </span>
                      </dd>
                    </div>
                    <div v-if="chapter.error_message" class="col-span-2">
                      <dt class="text-sm font-medium text-red-500">Error</dt>
                      <dd class="mt-1 text-sm text-red-500">{{ chapter.error_message }}</dd>
                    </div>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            v-if="!chapter.isPending"
            @click="generateVideo"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Generate Video
          </button>
          <button
            @click="close"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import VideoPreview from './VideoPreview.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  chapter: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'generate-video']);

const close = () => {
  emit('close');
};

const generateVideo = () => {
  emit('generate-video', props.chapter);
};
</script>