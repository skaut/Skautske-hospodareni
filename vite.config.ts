import { resolve } from 'node:path';

import { defineConfig } from 'vite';

// All build output goes into `www/dist`, a gitignored directory served by the
// PHP application (its document root is `www`). Keeping every generated file
// under a single dedicated folder separates build artifacts from both the
// committed assets in `www` and the sources in `frontend`. The bundle file
// names are referenced verbatim from the Latte templates
// (`/dist/js/app.min.js`, `/dist/css/app.css`), so they must stay stable.
//
// A plain Rollup entry (rather than Vite's `build.lib` mode) is used on
// purpose: library mode always inlines CSS-referenced assets as base64 data
// URIs, whereas here the icon fonts must be emitted as separate files (as they
// were under Webpack).
export default defineConfig(({ mode }) => ({
    // Generated asset URLs (e.g. fonts referenced from the CSS) are served from
    // `/dist/`, matching the output directory below.
    base: '/dist/',
    build: {
        outDir: 'www/dist',
        // The output directory holds nothing but build artifacts, so it is safe
        // (and desirable) to wipe it on every build to avoid stale files.
        emptyOutDir: true,
        // Emit a single stylesheet instead of per-chunk CSS.
        cssCodeSplit: false,
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
            output: {
                // A classic (non-module) self-executing script, included via a
                // plain <script src> tag.
                format: 'iife',
                name: 'app',
                inlineDynamicImports: true,
                entryFileNames: 'js/app.min.js',
                assetFileNames: (assetInfo): string => {
                    const name = assetInfo.names?.[0] ?? '';
                    if (name.endsWith('.css')) {
                        return 'css/app.css';
                    }
                    // Fonts and other assets referenced from the CSS, emitted
                    // to the `dist` root with a content hash.
                    return '[name]-[hash][extname]';
                },
            },
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
