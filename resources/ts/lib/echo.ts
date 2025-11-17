import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';

export const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: pusherCluster,
    wsHost: import.meta.env.VITE_PUSHER_HOST
        ? import.meta.env.VITE_PUSHER_HOST
        : `ws-${pusherCluster}.pusher.com`,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    withCredentials: true,
});
