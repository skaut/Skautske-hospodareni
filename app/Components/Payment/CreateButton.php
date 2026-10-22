<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;

use function array_merge;

class CreateButton extends BaseControl
{
    /** @var array<string, string> */
    protected array $css = [];

    /** Popisek hlavního tlačítka; bez něj „Založit skupinu plateb“. */
    private ?string $label = null;

    public function __construct()
    {
        $this->css = [
            'wrap' => 'd-inline-block ms-2',
            'group' => 'btn-group',
            'main' => 'btn btn-sm btn-primary',
            'toggle' => 'btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split',
            'menu' => 'dropdown-menu dropdown-menu-end',
        ];
    }

    /** @param array<string, string> $css */
    public function addCss(array $css): void
    {
        $this->css = array_merge($this->css, $css);
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function render(): void
    {
        $this->template->setParameters([
            'css' => $this->css,
            'label' => $this->label,
        ]);
        $this->template->setFile(__DIR__.'/templates/CreateButton.latte');
        $this->template->render();
    }
}
