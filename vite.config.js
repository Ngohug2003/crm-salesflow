import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const hmrClientPort = Number(process.env.VITE_HMR_CLIENT_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: process.env.VITE_HMR_HOST ?? 'localhost',
            clientPort: hmrClientPort,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
            usePolling: process.env.VITE_USE_POLLING === 'true',
        },
    },
});
