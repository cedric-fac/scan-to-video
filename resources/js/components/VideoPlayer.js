import { ref, onMounted, onUnmounted } from 'vue';

export default {
  name: 'VideoPlayer',
  props: {
    src: {
      type: String,
      required: true
    },
    autoplay: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const videoRef = ref(null);
    const isPlaying = ref(false);
    const volume = ref(1);
    const currentTime = ref(0);
    const duration = ref(0);
    const showControls = ref(true);
    const error = ref(null);
    const retryCount = ref(0);
    const maxRetries = 3;

    const togglePlay = () => {
      if (videoRef.value) {
        if (isPlaying.value) {
          videoRef.value.pause();
        } else {
          videoRef.value.play().catch(handleError);
        }
      }
    };

    const handleVolumeChange = (e) => {
      if (videoRef.value) {
        volume.value = parseFloat(e.target.value);
        videoRef.value.volume = volume.value;
      }
    };

    const seek = (e) => {
      if (videoRef.value) {
        const rect = e.target.getBoundingClientRect();
        const pos = (e.clientX - rect.left) / rect.width;
        videoRef.value.currentTime = pos * videoRef.value.duration;
      }
    };

    const handleError = (e) => {
      error.value = e.message || 'An error occurred during playback';
      if (retryCount.value < maxRetries) {
        retryCount.value++;
        setTimeout(() => {
          if (videoRef.value) {
            videoRef.value.load();
            if (isPlaying.value) {
              videoRef.value.play().catch(handleError);
            }
          }
        }, 1000 * retryCount.value);
      }
    };

    const handleKeyboard = (e) => {
      switch(e.key.toLowerCase()) {
        case ' ':
        case 'k':
          e.preventDefault();
          togglePlay();
          break;
        case 'm':
          if (videoRef.value) {
            videoRef.value.muted = !videoRef.value.muted;
          }
          break;
        case 'arrowleft':
          if (videoRef.value) {
            videoRef.value.currentTime = Math.max(0, videoRef.value.currentTime - 5);
          }
          break;
        case 'arrowright':
          if (videoRef.value) {
            videoRef.value.currentTime = Math.min(duration.value, videoRef.value.currentTime + 5);
          }
          break;
        case 'arrowup':
          volume.value = Math.min(1, volume.value + 0.1);
          if (videoRef.value) videoRef.value.volume = volume.value;
          break;
        case 'arrowdown':
          volume.value = Math.max(0, volume.value - 0.1);
          if (videoRef.value) videoRef.value.volume = volume.value;
          break;
      }
    };

    onMounted(() => {
      if (videoRef.value) {
        videoRef.value.addEventListener('timeupdate', () => {
          currentTime.value = videoRef.value.currentTime;
        });
        videoRef.value.addEventListener('loadedmetadata', () => {
          duration.value = videoRef.value.duration;
        });
        videoRef.value.addEventListener('play', () => {
          isPlaying.value = true;
        });
        videoRef.value.addEventListener('pause', () => {
          isPlaying.value = false;
        });
        videoRef.value.addEventListener('error', handleError);
      }
      window.addEventListener('keydown', handleKeyboard);
    });

    onUnmounted(() => {
      window.removeEventListener('keydown', handleKeyboard);
    });

    return {
      videoRef,
      isPlaying,
      volume,
      currentTime,
      duration,
      showControls,
      error,
      togglePlay,
      handleVolumeChange,
      seek
    };
  },
  template: `
    <div class="video-preview-container" @mousemove="showControls = true" @mouseleave="showControls = false">
      <video
        ref="videoRef"
        :src="src"
        :autoplay="autoplay"
        class="w-full"
        @click="togglePlay"
      ></video>

      <div v-if="error" class="absolute top-0 left-0 right-0 bg-red-500 text-white p-2 text-center">
        {{ error }}
      </div>

      <div v-show="showControls" class="video-controls absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 p-4">
        <div class="progress-bar" @click="seek">
          <div class="progress-bar-fill" :style="{ width: (currentTime / duration * 100) + '%' }"></div>
        </div>

        <div class="flex items-center justify-between mt-2">
          <div class="flex items-center space-x-4">
            <button @click="togglePlay" class="text-white hover:text-blue-400 transition-colors">
              <svg v-if="!isPlaying" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>

            <div class="flex items-center space-x-2 text-white">
              <input
                type="range"
                min="0"
                max="1"
                step="0.1"
                :value="volume"
                @input="handleVolumeChange"
                class="volume-slider w-20"
              />
              <span class="text-sm">{{ Math.round(volume * 100) }}%</span>
            </div>

            <div class="text-white text-sm">
              {{ Math.floor(currentTime / 60) }}:{{ Math.floor(currentTime % 60).toString().padStart(2, '0') }} /
              {{ Math.floor(duration / 60) }}:{{ Math.floor(duration % 60).toString().padStart(2, '0') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  `
};