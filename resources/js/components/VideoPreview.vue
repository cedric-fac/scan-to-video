<template>
  <div class="video-preview-container" @keydown="handleKeyboardShortcuts" tabindex="0" @mousemove="showControlsTemporarily">
    <div v-if="videoUrl" class="relative">
      <video
        ref="videoPlayer"
        class="w-full rounded-lg shadow-lg"
        :controls="false"
        :src="videoUrl"
        @loadedmetadata="onVideoLoaded"
        @error="onVideoError"
        @timeupdate="onTimeUpdate"
        @ended="onVideoEnded"
        @click="togglePlay"
      ></video>
      
      <!-- Custom Video Controls -->
      <div class="video-controls absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 p-2"
           :class="{ 'opacity-0': !showControls && !loading && !error }">
        <div class="flex items-center space-x-2">
          <button @click="togglePlay" class="text-white hover:text-blue-500 focus:outline-none">
            <svg v-if="!isPlaying" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
            </svg>
          </button>
          
          <!-- Progress Bar -->
          <div class="progress-bar flex-grow relative rounded" @click="seek" @mousemove="updatePreviewTime">
            <div class="progress-bar-fill absolute h-full rounded" :style="{ width: `${progress}%` }"></div>
            <div v-if="showTimePreview" class="absolute -top-8 transform -translate-x-1/2 bg-black bg-opacity-75 px-2 py-1 rounded text-white text-xs"
                 :style="{ left: `${previewPosition}%` }">
              {{ formatTime(previewTime) }}
            </div>
          </div>
          
          <!-- Time Display -->
          <div class="text-white text-sm">
            {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
          </div>
          
          <!-- Volume Control -->
          <div class="relative group">
            <button @click="toggleMute" class="text-white hover:text-blue-500 focus:outline-none">
              <svg v-if="!isMuted" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M12 8v8l-4-4H4V12h4l4-4z" />
              </svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
              </svg>
            </button>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block">
              <input
                type="range"
                min="0"
                max="1"
                step="0.1"
                v-model="volume"
                class="volume-slider w-24"
                @input="updateVolume"
              />
            </div>
          </div>
          
          <!-- Playback Speed -->
          <div class="relative group">
            <button class="text-white hover:text-blue-500 focus:outline-none text-sm font-medium">
              {{ playbackSpeed }}x
            </button>
            <div class="playback-speed-menu absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block">
              <div class="flex flex-col space-y-1">
                <button
                  v-for="speed in [0.5, 1, 1.5, 2]"
                  :key="speed"
                  @click="setPlaybackSpeed(speed)"
                  class="playback-speed-option text-white text-sm rounded"
                  :class="{ 'bg-blue-500 bg-opacity-50': playbackSpeed === speed }"
                >
                  {{ speed }}x
                </button>
              </div>
            </div>
          </div>
          
          <!-- Fullscreen Toggle -->
          <button @click="toggleFullscreen" class="text-white hover:text-blue-500 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="!isFullscreen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h6m0 0v6m0-6L4 20m16-6h-6m0 0v6m0-6l6 6M4 4h6m0 0v6M4 4l6 6m10-6h-6m0 0v6m0-6l6 6" />
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Loading Spinner -->
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>
    </div>
    
    <!-- No Video State -->
    <div v-else class="flex items-center justify-center h-48 bg-gray-100 dark:bg-gray-700 rounded-lg">
      <p class="text-gray-500 dark:text-gray-400">No video available</p>
    </div>
    
    <!-- Error State with Retry -->
    <div v-if="error" class="mt-2 flex items-center space-x-2">
      <span class="text-red-500 text-sm">{{ error }}</span>
      <button @click="retryLoading" class="px-3 py-1 text-sm text-white bg-blue-500 rounded hover:bg-blue-600 focus:outline-none">
        Retry
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  videoUrl: {
    type: String,
    default: ''
  }
});

const videoPlayer = ref(null);
const loading = ref(true);
const error = ref(null);
const isPlaying = ref(false);
const isMuted = ref(false);
const isFullscreen = ref(false);
const showControls = ref(true);
const currentTime = ref(0);
const duration = ref(0);
const progress = ref(0);
const volume = ref(1);
const playbackSpeed = ref(1);
let controlsTimeout = null;

const onVideoLoaded = () => {
  loading.value = false;
  error.value = null;
  duration.value = videoPlayer.value.duration;
};

const onVideoError = () => {
  loading.value = false;
  error.value = 'Failed to load video';
};

const onTimeUpdate = () => {
  currentTime.value = videoPlayer.value.currentTime;
  progress.value = (currentTime.value / duration.value) * 100;
};

const onVideoEnded = () => {
  isPlaying.value = false;
};

const togglePlay = () => {
  if (videoPlayer.value.paused) {
    videoPlayer.value.play();
    isPlaying.value = true;
  } else {
    videoPlayer.value.pause();
    isPlaying.value = false;
  }
};

const toggleMute = () => {
  videoPlayer.value.muted = !videoPlayer.value.muted;
  isMuted.value = videoPlayer.value.muted;
};

const updateVolume = () => {
  videoPlayer.value.volume = volume.value;
  isMuted.value = volume.value === 0;
};

const setPlaybackSpeed = (speed) => {
  playbackSpeed.value = speed;
  videoPlayer.value.playbackRate = speed;
};

const seek = (event) => {
  const rect = event.target.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const percentage = x / rect.width;
  videoPlayer.value.currentTime = percentage * duration.value;
};

const formatTime = (time) => {
  const minutes = Math.floor(time / 60);
  const seconds = Math.floor(time % 60);
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};

const toggleFullscreen = async () => {
  try {
    if (!document.fullscreenElement) {
      await videoPlayer.value.requestFullscreen();
      isFullscreen.value = true;
    } else {
      await document.exitFullscreen();
      isFullscreen.value = false;
    }
  } catch (err) {
    console.error('Fullscreen error:', err);
  }
};

const handleKeyboardShortcuts = (event) => {
  switch (event.key.toLowerCase()) {
    case ' ':
    case 'k':
      event.preventDefault();
      togglePlay();
      break;
    case 'f':
      event.preventDefault();
      toggleFullscreen();
      break;
    case 'm':
      event.preventDefault();
      toggleMute();
      break;
    case 'arrowleft':
      event.preventDefault();
      videoPlayer.value.currentTime -= 5;
      break;
    case 'arrowright':
      event.preventDefault();
      videoPlayer.value.currentTime += 5;
      break;
    case 'arrowup':
      event.preventDefault();
      volume.value = Math.min(1, volume.value + 0.1);
      updateVolume();
      break;
    case 'arrowdown':
      event.preventDefault();
      volume.value = Math.max(0, volume.value - 0.1);
      updateVolume();
      break;
  }
};

const retryLoading = () => {
  if (props.videoUrl) {
    loading.value = true;
    error.value = null;
    videoPlayer.value.load();
  }
};

const showControlsTemporarily = () => {
  showControls.value = true;
  if (controlsTimeout) {
    clearTimeout(controlsTimeout);
  }
  controlsTimeout = setTimeout(() => {
    if (!videoPlayer.value.paused) {
      showControls.value = false;
    }
  }, 3000);
};

watch(() => props.videoUrl, () => {
  if (props.videoUrl) {
    loading.value = true;
    error.value = null;
  }
});

onMounted(() => {
  if (props.videoUrl) {
    loading.value = true;
  }
  
  if (videoPlayer.value) {
    videoPlayer.value.addEventListener('mousemove', showControlsTemporarily);
  }
});

onUnmounted(() => {
  if (controlsTimeout) {
    clearTimeout(controlsTimeout);
  }
  
  if (videoPlayer.value) {
    videoPlayer.value.removeEventListener('mousemove', showControlsTemporarily);
  }
});
</script>