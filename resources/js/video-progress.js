import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

class VideoProgressManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        document.querySelectorAll('[data-chapter-id]').forEach(element => {
            const chapterId = element.dataset.chapterId;
            this.listenToProgressUpdates(chapterId);
        });
    }

    listenToProgressUpdates(chapterId) {
        window.Echo.private(`video.generation.${chapterId}`)
            .listen('VideoGenerationProgress', (e) => {
                this.updateProgressUI(chapterId, e.progress, e.estimatedTime, e.status, e.errorMessage);
            });
    }

    updateProgressUI(chapterId, progress, estimatedTime, status, errorMessage) {
        const container = document.querySelector(`[data-chapter-id="${chapterId}"]`);
        if (!container) return;

        const progressBar = container.querySelector('.progress-bar');
        const progressText = container.querySelector('.progress-text');
        const estimatedTimeText = container.querySelector('.estimated-time');
        const statusBadge = container.querySelector('.status-badge');
        const generateButton = container.querySelector('.generate-button');

        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        if (progressText) {
            progressText.textContent = `Progress: ${progress}%`;
        }

        if (estimatedTimeText && estimatedTime) {
            estimatedTimeText.textContent = `Est. Time: ${estimatedTime} min`;
        }

        if (statusBadge) {
            statusBadge.className = `px-2 py-1 text-xs font-semibold rounded-full status-badge ${
                status === 'processed' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' :
                status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' :
                'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
            }`;
            statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }

        if (generateButton) {
            generateButton.className = `${
                status === 'pending' ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-500 hover:bg-indigo-600'
            } text-white text-sm font-bold py-2 px-4 rounded transition duration-150 ease-in-out`;
            generateButton.disabled = status === 'pending';
            generateButton.textContent = status === 'pending' ? 'Processing...' : 'Generate Video';
        }

        if (errorMessage) {
            const errorElement = container.querySelector('.error-message') || document.createElement('span');
            errorElement.className = 'text-xs text-red-600 dark:text-red-400 ml-2 error-message';
            errorElement.textContent = `(${errorMessage})`;
            if (!container.querySelector('.error-message')) {
                statusBadge.parentNode.appendChild(errorElement);
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new VideoProgressManager();
});

function showConnectionError() {
    const errorToast = document.createElement('div');
    errorToast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
    errorToast.innerHTML = `
        <div class="flex items-center space-x-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Connection lost. Attempting to reconnect...</span>
        </div>
    `;
    document.body.appendChild(errorToast);

    // Remove the toast after 5 seconds
    setTimeout(() => {
        errorToast.remove();
    }, 5000);
}