import Echo from 'laravel-echo';
import WebSocketManager from './websocket-manager';

export default class ProgressTracker {
    constructor(channelName) {
        this.channelName = channelName;
        this.listeners = [];
        this.errorHandlers = [];
    }

    subscribe(callback, errorHandler = null) {
        WebSocketManager.subscribeToProgress(
            this.channelName,
            (data) => {
                callback(data);
                this.listeners.push(callback);
            },
            errorHandler ? (error) => {
                errorHandler(error);
                this.errorHandlers.push(errorHandler);
            } : null
        );
    }

    unsubscribe(callback) {
        const index = this.listeners.indexOf(callback);
        if (index > -1) {
            this.listeners.splice(index, 1);
        }

        if (this.listeners.length === 0) {
            WebSocketManager.unsubscribe(this.channelName);
        }
    }

    unsubscribeAll() {
        WebSocketManager.unsubscribe(this.channelName);
        this.listeners = [];
        this.errorHandlers = [];
    }

    static initializeForChapter(chapterId) {
        return new ProgressTracker(`chapter.${chapterId}`);
    }

    static initializeForVideo(videoId) {
        return new ProgressTracker(`video.${videoId}`);
    }
}