import { resolve } from 'node:path';

import { defineConfig } from 'vite';

// The frontend is bundled straight into the `www` directory that the PHP
// application serves. The output file names are referenced verbatim from the
// Latte templates (`/js/app.min.js`, `/css/app.css`), so they must stay stable.
//
// A plain Rollup entry (rather than Vite's `build.lib` mode) is used on
// purpose: library mode always inlines CSS-referenced assets as base64 data
// URIs, whereas here the icon fonts must be emitted as separate files (as they
// were under Webpack).
export default defineConfig(({ mode }) => ({
    build: {
        outDir: 'www',
        // `www` also contains committed, non-generated assets (index.php,
        // images, other CSS, …) so it must never be wiped before a build.
        emptyOutDir: false,
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
                    // to the `www` root with a content hash (as with Webpack).
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
