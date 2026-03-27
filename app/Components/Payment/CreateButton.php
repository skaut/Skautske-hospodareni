<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;

use function array_merge;

class CreateButton extends BaseControl
{
    /** @var array<string, string> */
    protected array $css = [];

    public function __construct()
    {
        $this->css = [
            'wrap' => 'd-inline-block ms-2',
            'group' => 'btn-group',
            'main' => 'btn btn-success',
            'toggle' => 'btn btn-success dropdown-toggle dropdown-toggle-split',
            'menu' => 'dropdown-menu dropdown-menu-end',
        ];
    }

    /** @param array<string, string> $css */
    public function addCss(array $css): void
    {
        $this->css = array_merge($this->css, $css);
    }

    public function render(): void
    {
        $this->template->setParameters([
            'css' => $this->css,
        ]);
        $this->template->setFile(__DIR__.'/templates/CreateButton.latte');
        $this->template->render();
    }
}
