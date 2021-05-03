<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\BasePresenter;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\InvalidStateException;
use stdClass;

/**
 * @property-read DefaultTemplate $template
 * @property-read BasePresenter $presenter
 */
abstract class BaseControl extends Control
{
    abstract public function render(): void;

    public function getPresenter(): ?BasePresenter
    {
        $presenter = parent::getPresenter();

        if (! $presenter instanceof BasePresenter) {
            throw new InvalidStateException(
                'Presenter using BaseControl derived controls must inherit from ' . BasePresenter::class
            );
        }

        return $presenter;
    }

    /**
     * @param string $message
     * @param string $type
     */
    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    public function flashMessage($message, $type = 'info'): stdClass
    {
        return $this->getPresenter()->flashMessage($message, $type);
    }

    protected function reload(?string $message = null, string $type = 'info'): void
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
