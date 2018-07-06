<?php


namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\BaseControl;
use Ublaboo\DataGrid\DataGrid;

abstract class BaseGridControl extends BaseControl
{

    public function render() : void
    {
        $this->redrawControl('main');
        $this->template->setFile(__DIR__ . '/templates/BaseGridControl.latte');
        $this->template->render();
    }

    abstract protected function createComponentGrid() : DataGrid;

    protected function createGrid() : DataGrid
    {
        $grid = (new GridFactory())->create();
        $grid->setRememberState(FALSE); // It's mostly WTF

        return $grid;
    }

}
