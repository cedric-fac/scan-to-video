import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

class WebSocketManager {
    constructor() {
        this.echo = null;
        this.subscriptions = new Map();
        this.retryAttempts = 0;
        this.maxRetryAttempts = 5;
        this.retryDelay = 1000;
        this.initialize();
    }

    initialize() {
        window.Pusher = Pusher;

        this.echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            wsHost: window.location.hostname,
            wsPort: 6001,
            wssPort: 6001,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            }
        });

        this.setupConnectionHandlers();
    }

    setupConnectionHandlers() {
        this.echo.connector.pusher.connection.bind('connected', () => {
            console.log('WebSocket connected successfully');
            this.retryAttempts = 0;
        });

        this.echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('WebSocket disconnected');
            this.handleDisconnection();
        });

        this.echo.connector.pusher.connection.bind('error', (error) => {
            console.error('WebSocket error:', error);
            this.handleError(error);
        });
    }

    handleDisconnection() {
        if (this.retryAttempts < this.maxRetryAttempts) {
            setTimeout(() => {
                console.log(`Attempting to reconnect (${this.retryAttempts + 1}/${this.maxRetryAttempts})`);
                this.echo.connector.pusher.connect();
                this.retryAttempts++;
            }, this.retryDelay * Math.pow(2, this.retryAttempts));
        } else {
            console.error('Max retry attempts reached. Please refresh the page.');
        }
    }

    handleError(error) {
        if (error.type === 'WebSocketError') {
            this.handleDisconnection();
        }
    }

    subscribeToProgress(channelName, progressCallback, errorCallback = null) {
        if (this.subscriptions.has(channelName)) {
            return;
        }

        try {
            const channel = this.echo.private(channelName);
            channel.listen('ProgressUpdate', (data) => {
                progressCallback(data);
            });

            if (errorCallback) {
                channel.error((error) => {
                    errorCallback(error);
                });
            }

            this.subscriptions.set(channelName, channel);
        } catch (error) {
            console.error(`Error subscribing to channel ${channelName}:`, error);
            if (errorCallback) {
                errorCallback(error);
            }
        }
    }

    unsubscribe(channelName) {
        if (this.subscriptions.has(channelName)) {
            try {
                this.echo.leave(channelName);
                this.subscriptions.delete(channelName);
            } catch (error) {
                console.error(`Error unsubscribing from channel ${channelName}:`, error);
            }
        }
    }

    unsubscribeAll() {
        this.subscriptions.forEach((_, channelName) => {
            this.unsubscribe(channelName);
        });
    }
}

export default new WebSocketManager();