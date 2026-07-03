<?php

declare(strict_types=1);

namespace App\Components;

final class DarkModeToggle extends BaseControl
{
    public function __construct()
    {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/DarkModeToggle.latte');
        $this->template->render();
    }
}
