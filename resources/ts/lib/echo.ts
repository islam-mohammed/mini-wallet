import Echo from 'laravel-echo'
import Pusher from 'pusher-js'


// eslint-disable-next-line @typescript-eslint/no-explicit-any
(window as any).Pusher = Pusher

export const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
  wsHost:
    import.meta.env.VITE_PUSHER_HOST ??
    `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1'}.pusher.com`,
  wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 80),
  wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 443),
  forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
})
