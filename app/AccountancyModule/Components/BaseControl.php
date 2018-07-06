<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\BasePresenter;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\InvalidStateException;

/**
 * @property-read Template $template
 * @property-read BasePresenter $presenter
 */
class BaseControl extends Control
{
    public function getPresenter($throw = true) : ?BasePresenter
    {
        $presenter = parent::getPresenter($throw);

        if (! $presenter instanceof BasePresenter) {
            throw new InvalidStateException(
                'Presenter using BaseControl derived controls must inherit from ' . BasePresenter::class
            );
        }

        return $presenter;
    }
}
