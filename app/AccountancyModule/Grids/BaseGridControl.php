<?php


namespace App\AccountancyModule\Factories;

use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

abstract class BaseGridControl extends Control
{

    public function render(): void
    {
        $this->redrawControl('main');
        $this->template->setFile(__DIR__ . '/templates/BaseGridControl.latte');
        $this->template->render();
    }

    abstract protected function createComponentGrid(): DataGrid;

    protected function createGrid(): DataGrid
    {
        $grid = (new GridFactory())->create();
        $grid->setRememberState(FALSE); // It's mostly WTF

        return $grid;
    }

}
