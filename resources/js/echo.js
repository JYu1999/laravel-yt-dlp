import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const resolvedHost = reverbHost === 'reverb' ? window.location.hostname : reverbHost;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 6001);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: resolvedHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: (reverbScheme === 'https'),
    enabledTransports: ['ws', 'wss'],
});
