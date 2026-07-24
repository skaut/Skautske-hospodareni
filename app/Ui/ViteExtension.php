<?php

declare(strict_types=1);

namespace App\Ui;

use Latte\Extension;

/**
 * Exposes the Vite manifest to Latte templates via the `viteJs()` and
 * `viteCss()` functions, e.g. `<script src={viteJs('frontend/app.ts')}>`.
 */
final class ViteExtension extends Extension
{
    public function __construct(private ViteManifest $manifest)
    {
    }

    /** @return array<string, callable> */
    public function getFunctions(): array
    {
        return [
            'viteJs' => $this->manifest->js(...),
            'viteCss' => $this->manifest->css(...),
        ];
    }
}
