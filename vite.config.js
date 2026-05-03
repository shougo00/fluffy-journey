import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/camera/camera.css', 
                'resources/css/group_history/index.blade.css',
                'resources/css/group_history/monthly.css',
                'resources/js/camera/camera.js',
                'resources/js/group/records.js',
                'resources/css/kyudo_results/index.css',
                'resources/css/lineup/index.css',
                'resources/css/group/records.css'
            ],
            refresh: true,
        }),
    ],
});