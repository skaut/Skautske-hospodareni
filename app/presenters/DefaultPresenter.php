<?php

declare(strict_types=1);

namespace App;

class DefaultPresenter extends BasePresenter
{
    protected function beforeRender() : void
    {
        parent::beforeRender();
        $this->setLayout('layout2');
    }

    public function renderDefault(?string $backlink = null) : void
    {
    }
}
