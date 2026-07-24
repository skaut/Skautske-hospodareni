<?php

declare(strict_types=1);

namespace App\Ui;

use RuntimeException;

use function array_keys;
use function array_map;
use function file_get_contents;
use function is_file;
use function json_decode;
use function rtrim;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Resolves Vite build entries to their content-hashed output URLs using the
 * build manifest (`dist/.vite/manifest.json`).
 *
 * Code-split builds are handled: an entry's stylesheets and preload targets are
 * gathered from the entry itself plus all of its statically imported (shared)
 * chunks, so shared-chunk CSS is not dropped.
 */
final class ViteManifest
{
    /** @var array<string, array{file: string, css?: list<string>, imports?: list<string>}>|null */
    private ?array $manifest = null;

    public function __construct(private string $manifestPath, private string $basePath)
    {
    }

    /** Public URL of an entry's JavaScript file. */
    public function js(string $entry): string
    {
        return $this->url($this->entry($entry)['file']);
    }

    /**
     * Public URLs of every stylesheet an entry needs: its own plus those of all
     * statically imported chunks, in dependency order (shared chunks first).
     *
     * @return list<string>
     */
    public function css(string $entry): array
    {
        $files = [];

        foreach ($this->importedChunks($entry) as $chunk) {
            foreach ($chunk['css'] ?? [] as $css) {
                $files[$css] = true;
            }
        }

        foreach ($this->entry($entry)['css'] ?? [] as $css) {
            $files[$css] = true;
        }

        return array_map($this->url(...), array_keys($files));
    }

    /**
     * Public URLs of the JS chunks an entry statically imports, for
     * `<link rel="modulepreload">`. Empty unless the build is code-split.
     *
     * @return list<string>
     */
    public function jsPreload(string $entry): array
    {
        $files = [];

        foreach ($this->importedChunks($entry) as $chunk) {
            $files[$chunk['file']] = true;
        }

        return array_map($this->url(...), array_keys($files));
    }

    /**
     * Every chunk an entry imports statically, transitively, in dependency
     * order (deepest first); the entry chunk itself is excluded.
     *
     * @return list<array{file: string, css?: list<string>, imports?: list<string>}>
     */
    private function importedChunks(string $entry): array
    {
        $manifest = $this->manifest ??= $this->load();
        $chunks = [];
        $seen = [];

        $walk = function (string $key) use (&$walk, $manifest, &$chunks, &$seen): void {
            foreach ($manifest[$key]['imports'] ?? [] as $import) {
                if (isset($seen[$import])) {
                    continue;
                }

                $seen[$import] = true;
                $walk($import);
                $chunks[] = $manifest[$import];
            }
        };

        $walk($entry);

        return $chunks;
    }

    /** @return array{file: string, css?: list<string>, imports?: list<string>} */
    private function entry(string $entry): array
    {
        $manifest = $this->manifest ??= $this->load();

        if (! isset($manifest[$entry])) {
            throw new RuntimeException(sprintf('Vite manifest has no entry "%s". Did you run the frontend build?', $entry));
        }

        return $manifest[$entry];
    }

    /** @return array<string, array{file: string, css?: list<string>, imports?: list<string>}> */
    private function load(): array
    {
        if (! is_file($this->manifestPath)) {
            throw new RuntimeException(sprintf('Vite manifest not found at "%s". Run the frontend build (yarn build).', $this->manifestPath));
        }

        return json_decode((string) file_get_contents($this->manifestPath), true, 512, JSON_THROW_ON_ERROR);
    }

    private function url(string $file): string
    {
        return rtrim($this->basePath, '/').'/'.$file;
    }
}
