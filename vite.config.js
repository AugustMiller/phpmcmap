import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const port = 5173;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
        }),
    ],
    server: {
        host: true,
        port: port,
        strictPort: true,
        hmr: {
            host: process.env.DDEV_HOSTNAME,
            protocol: 'wss',
        },
    },
});
