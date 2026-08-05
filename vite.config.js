import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'node:path';

/**
 * Le build sort dans webroot/build/ et ce dossier est COMMITTÉ : l'hébergement
 * mutualisé ne dispose pas de Node, `npm run build` tourne donc en local ou en CI.
 * Le manifeste est lu côté PHP par App\View\Helper\ViteHelper.
 */
export default defineConfig({
    base: '/build/',
    build: {
        outDir: resolve(import.meta.dirname, 'webroot/build'),
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: resolve(import.meta.dirname, 'resources/js/app.js'),
                admin: resolve(import.meta.dirname, 'resources/js/admin.js'),
            },
        },
    },
    plugins: [tailwindcss()],
});
