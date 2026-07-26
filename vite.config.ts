import { resolve } from 'node:path';

import { defineConfig } from 'vite';

// Output file names are content-hashed (Vite's default). The PHP side does not
// hard-code them: it reads `dist/.vite/manifest.json` to resolve the entry
// (`frontend/app.ts`) to its hashed JS and CSS URLs. This enables immutable
// long-term caching of the assets.
export default defineConfig(({ mode }) => ({
    // Generated asset URLs (in the manifest and in the built CSS) are served
    // from `/dist/`, matching the output directory below.
    base: '/dist/',
    build: {
        outDir: 'www/dist',
        // Emit `.vite/manifest.json` mapping the entry to its hashed files.
        manifest: true,
        sourcemap: mode === 'development',
        minify: mode !== 'development',
        rollupOptions: {
            input: resolve(import.meta.dirname, 'frontend/app.ts'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
            },
        },
    },
}));
