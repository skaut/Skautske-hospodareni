<?php

declare(strict_types=1);

namespace App\Ui;

use RuntimeException;

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
 */
final class ViteManifest
{
    /** @var array<string, array{file: string, css?: list<string>}>|null */
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
     * Public URLs of all stylesheets belonging to an entry.
     *
     * @return list<string>
     */
    public function css(string $entry): array
    {
        return array_map($this->url(...), $this->entry($entry)['css'] ?? []);
    }

    /** @return array{file: string, css?: list<string>} */
    private function entry(string $entry): array
    {
        $manifest = $this->manifest ??= $this->load();

        if (! isset($manifest[$entry])) {
            throw new RuntimeException(sprintf('Vite manifest has no entry "%s". Did you run the frontend build?', $entry));
        }

        return $manifest[$entry];
    }

    /** @return array<string, array{file: string, css?: list<string>}> */
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
