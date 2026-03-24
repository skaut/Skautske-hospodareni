<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Default;

final class DefaultPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    public function renderDefault(): void
    {
        $this->setSettingsTemplateParameters();
    }
}
