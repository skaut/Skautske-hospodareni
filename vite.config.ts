import { resolve } from 'node:path';

import { defineConfig } from 'vite';

// The PHP side resolves the application entry through the Vite manifest. The
// application assets are content-hashed below www/dist/assets, while the
// service worker stays at www/sw.js so it can control the whole application.
export default defineConfig(({ mode }) => ({
    // Output paths already start with dist/, so serve generated URLs from the application root.
    base: '/',
    build: {
        outDir: 'www',
        // www contains committed application files as well as build output.
        emptyOutDir: false,
        manifest: 'dist/.vite/manifest.json',
        sourcemap: mode === 'development',
        minify: mode !== 'development',
        rollupOptions: {
            input: [
                resolve(import.meta.dirname, 'frontend/app.ts'),
                resolve(import.meta.dirname, 'frontend/sw/serviceWorker.ts'),
            ],
            output: {
                entryFileNames: (chunkInfo): string => chunkInfo.facadeModuleId?.endsWith('/frontend/sw/serviceWorker.ts')
                    ? 'sw.js'
                    : 'dist/assets/[name]-[hash].js',
                assetFileNames: 'dist/assets/[name]-[hash][extname]',
            },
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
