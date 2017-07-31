<?php

namespace App\AccountancyModule\Components;


use App\BasePresenter;
use Nette\Application\UI\Control;
use Nette\InvalidStateException;

class BaseControl extends Control
{

    public function getPresenter($throw = TRUE): ?BasePresenter
    {
        $presenter = parent::getPresenter($throw);

        if( ! $presenter instanceof BasePresenter) {
            throw new InvalidStateException(
                'Presenter using BaseControl derived controls must inherit from ' . BasePresenter::class
            );
        }

        return $presenter;
    }

}
