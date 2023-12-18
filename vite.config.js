import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
        }),
    ],
    server: {
        host: true,
        strictPort: true,
        port: 5173,
        hmr: {
            protocol: 'wss',
            host: `${process.env.DDEV_HOSTNAME}`,
        },
    },
});
