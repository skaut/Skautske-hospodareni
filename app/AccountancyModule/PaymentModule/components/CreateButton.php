<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseButtonControl;

class CreateButton extends BaseButtonControl
{
    public function render(): void
    {
        $this->template->setParameters([
            'css'     => $this->css,
        ]);
        $this->template->setFile(__DIR__ . '/templates/CreateButton.latte');
        $this->template->render();
    }
}
