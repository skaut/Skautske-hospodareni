<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\BasePresenter;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\InvalidStateException;
use stdClass;

/**
 * @property-read Template $template
 * @property-read BasePresenter $presenter
 */
class BaseControl extends Control
{
    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function flashMessage($message, $type = 'info') : stdClass
    {
        return $this->getPresenter()->flashMessage($message, $type);
    }

    protected function reload(?string $message = null, string $type = 'info') : void
    {
        if ($message !== null) {
            $this->flashMessage($message, $type);
        }
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }
}
