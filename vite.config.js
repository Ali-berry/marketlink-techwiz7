import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/market-map.js',
                'resources/js/insights-chart.js',
                'resources/js/stall-location-map.js',
                'resources/js/markets-distance-sort.js',
                'resources/js/reports-charts.js',
                'resources/js/chat-composer.js',
                'resources/js/agent-chat.js',
                'resources/js/command-palette.js',
                'resources/js/home-hero-slider.js',
            ],
            refresh: true,
        }),
    ],
});
