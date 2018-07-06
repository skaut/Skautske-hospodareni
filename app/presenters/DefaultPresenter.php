<?php

declare(strict_types=1);

namespace App;

class DefaultPresenter extends BasePresenter
{
    protected function startup() : void
    {
        parent::startup();
    }

    public function renderDefault($backlink = null) : void
    {
    }
}
