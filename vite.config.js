import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    const assetName = assetInfo.names?.[0] ?? assetInfo.name ?? '';

                    if (assetName.endsWith('.mjs')) {
                        return 'assets/[name]-[hash].js';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pdf-presentation-viewer.js', 'resources/css/filament/admin/theme.css', 'resources/css/filament/instructor/theme.css', 'resources/css/filament/student/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
