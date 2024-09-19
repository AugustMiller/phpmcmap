import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const port = 5172;
const origin = `${process.env.DDEV_PRIMARY_URL}:${port}`;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        // respond to all network requests
        host: true,
        port: port,
        strictPort: true,
        // Defines the origin of the generated asset URLs during development,
        // this will also be used for the public/hot file (Vite devserver URL)
        // origin,
        hmr: {
            // host: process.env.DDEV_PRIMARY_URL,
            // host: `${process.env.DDEV_HOSTNAME}:${port}`,
            host: process.env.DDEV_HOSTNAME,
            // server: 'wss',
        },
    },
});
