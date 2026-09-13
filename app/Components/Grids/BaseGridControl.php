<?php

declare(strict_types=1);

namespace App\Components\Grids;

use App\Components\BaseControl;
use Contributte\Datagrid\Datagrid;

abstract class BaseGridControl extends BaseControl
{
    public function render(): void
    {
        $this->redrawControl('main');
        $this->template->setFile(__DIR__.'/templates/BaseGridControl.latte');
        $this->template->render();
    }

    abstract protected function createComponentGrid(): Datagrid;

    protected function createGrid(): Datagrid
    {
        $grid = (new GridFactory())->create();
        $grid->setRememberState(false); // It's mostly WTF

        return $grid;
    }
}
