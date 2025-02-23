import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

import ProgressTracker from './progress-tracker';

const updateChapterProgress = (chapterId, progress, status) => {
    const progressBar = document.querySelector(`#chapter-${chapterId} .progress-bar`);
    const progressText = document.querySelector(`#chapter-${chapterId} .progress-text`);
    const statusBadge = document.querySelector(`#chapter-${chapterId} .status-badge`);
    
    if (progressBar && progressText) {
        progressBar.style.width = `${progress}%`;
        progressText.textContent = `${progress}%`;
    }

    if (statusBadge && status) {
        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        
        statusBadge.classList.remove('bg-yellow-100', 'bg-green-100', 'bg-red-100',
            'text-yellow-800', 'text-green-800', 'text-red-800');
        
        switch(status) {
            case 'processing':
                statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                break;
            case 'processed':
                statusBadge.classList.add('bg-green-100', 'text-green-800');
                break;
            case 'failed':
                statusBadge.classList.add('bg-red-100', 'text-red-800');
                break;
        }
    }
};

class ChapterProgressManager {
    constructor() {
        this.progressTrackers = new Map();
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        document.querySelectorAll('[data-chapter-id]').forEach(element => {
            const chapterId = element.dataset.chapterId;
            this.listenToProgressUpdates(chapterId);
        });
    }

    listenToProgressUpdates(chapterId) {
        const tracker = ProgressTracker.initializeForChapter(chapterId);
        
        tracker.subscribe(
            (data) => {
                updateChapterProgress(chapterId, data.progress, data.status);
            },
            (error) => {
                console.error(`Error tracking progress for chapter ${chapterId}:`, error);
                const statusBadge = document.querySelector(`#chapter-${chapterId} .status-badge`);
                if (statusBadge) {
                    statusBadge.textContent = 'Error';
                    statusBadge.classList.remove('bg-yellow-100', 'bg-green-100');
                    statusBadge.classList.add('bg-red-100', 'text-red-800');
                }
            }
        );

        this.progressTrackers.set(chapterId, tracker);
    }

    cleanup() {
        this.progressTrackers.forEach(tracker => tracker.unsubscribeAll());
        this.progressTrackers.clear();
    }
}

// Initialize the chapter progress manager
const chapterProgressManager = new ChapterProgressManager();

// Cleanup on page unload
window.addEventListener('unload', () => {
    chapterProgressManager.cleanup();
});

// Listen for chapter progress updates
window.Echo.private('chapter-progress')
    .listen('ChapterProgressUpdated', (e) => {
        updateChapterProgress(e.chapterId, e.progress, e.status);
    });