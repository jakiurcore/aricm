import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laramod from 'laramod-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laramod({
            input: ['resources/css/app.css'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/vendor/**', '**/storage/**', '**/design/**'],
        },
    },
});
