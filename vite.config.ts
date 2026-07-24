import { resolve } from 'node:path';

import { defineConfig } from 'vite';

// All build output goes into `www/dist`, a gitignored directory served by the
// PHP application (its document root is `www`). Keeping every generated file
// under a single dedicated folder separates build artifacts from both the
// committed assets in `www` and the sources in `frontend`.
//
// Output file names are content-hashed (Vite's default). The PHP side does not
// hard-code them: it reads `dist/.vite/manifest.json` to resolve the entry
// (`frontend/app.ts`) to its hashed JS and CSS URLs. This enables immutable
// long-term caching of the assets.
//
// The bundle is emitted as an ES module (Vite's default) and loaded via
// `<script type="module">`. This is what lets the manifest track the entry's
// stylesheet as a separate file (`build.lib` / an `iife` output would instead
// inline the CSS into the JS bundle).
export default defineConfig(({ mode }) => ({
    // Generated asset URLs (in the manifest and in the built CSS) are served
    // from `/dist/`, matching the output directory below.
    base: '/dist/',
    build: {
        outDir: 'www/dist',
        // The output directory holds nothing but build artifacts, so it is safe
        // (and desirable) to wipe it on every build to avoid stale files.
        emptyOutDir: true,
        // Emit `.vite/manifest.json` mapping the entry to its hashed files.
        manifest: true,
        sourcemap: mode === 'development',
        minify: mode !== 'development',
        commonjsOptions: {
            // netteForms.js (a UMD module shipped by the PHP dependency) lives
            // outside node_modules, so it must be opted into CommonJS interop.
            include: [/node_modules/, /vendor\/nette\/forms/],
            transformMixedEsModules: true,
        },
        rollupOptions: {
            input: resolve(import.meta.dirname, 'frontend/app.ts'),
        },
    },
    resolve: {
        alias: {
            '@': resolve(import.meta.dirname, 'frontend'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
                // Silence warnings from dependencies (Bootstrap, Tabler, …).
                quietDeps: true,
                silenceDeprecations: ['color-functions', 'global-builtin', 'import'],
            },
        },
    },
}));
