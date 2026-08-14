import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        // Force IPv4 loopback so Vite's dev-server origin is a valid CSP
        // source (CSP does not accept bracketed IPv6 like [::1]).
        host: '127.0.0.1',
        port: 5173,
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: [
                'resources/routes/**',
                'resources/views/**',
                'modules/**/routes/**',
                'modules/**/Resources/views/**',
                'modules/**/Resources/js/**/*.{vue,js,ts}',
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
