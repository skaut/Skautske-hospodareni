<?php

declare(strict_types=1);

namespace App\Components;

use App\AccountancyModule\Components\BaseControl;

final class DarkModeToggle extends BaseControl
{
    public function __construct()
    {
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/DarkModeToggle.latte');
        $this->template->render();
    }
}
