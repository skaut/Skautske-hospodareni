<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use function array_merge;

abstract class BaseButtonControl extends BaseControl
{
    /** @var string[] */
    protected array $css = [];

    /** @param array<string, string> $css */
    public function addCss(array $css): void
    {
        $this->css = array_merge($this->css, $css);
    }

    public function setCss(string $key, string $value): void
    {
        $this->css[$key] = $value;
    }
}
