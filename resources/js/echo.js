import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '');
const port = Number(import.meta.env.VITE_REVERB_PORT ?? (scheme === 'https' ? 443 : 80));

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});

const connection = window.Echo.connector.pusher.connection;

const publishState = (state) => {
    window.salesflowRealtimeState = state;
    window.dispatchEvent(new CustomEvent('salesflow-realtime-state', { detail: { state } }));
};

publishState(connection.state);
connection.bind('state_change', ({ current }) => publishState(current));
