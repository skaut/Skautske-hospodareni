<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Default;

final class DefaultPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'overview',
            'unitId' => $this->unitId->toInt(),
        ]);
    }
}
