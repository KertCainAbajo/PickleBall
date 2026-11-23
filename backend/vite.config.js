import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // UI face: athletic signage grotesk with tabular figures.
                bunny('Barlow', {
                    weights: [400, 500, 600, 700],
                }),
                // Times, prices, compact data.
                bunny('Barlow Condensed', {
                    weights: [500, 600, 700],
                }),
                // Varsity-style display font matching the logo lettering.
                bunny('Graduate', {
                    weights: [400],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
