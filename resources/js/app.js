import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Reverb client. When no WebSocket env is configured (bare local dev),
// window.Echo stays undefined and the wall simply doesn't live-update -
// everything else works.
window.Pusher = Pusher;
const key = import.meta.env.VITE_REVERB_APP_KEY;
if (key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

import './game.js';
import './wall.js';
