<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use Nette\InvalidStateException;

/**
 * Abstract parent that implements basic functionality related to making modal dialogs.
 *
 * To create custom dialog simply extend this class and set template file (that extends from $layout) in beforeRender()
 * Parts of dialog can be changed by overwriting blocks `dialog-title`, `dialog-body`, `dialog-footer`.
 */
abstract class Dialog extends BaseControl
{
    /**
     * @internal
     *
     * @persistent
     */
    public bool $opened = false;

    protected function beforeRender() : void
    {
    }

    final public function render() : void
    {
        $this->beforeRender();

        $this->template->setParameters([
            'layout' => __DIR__ . '/templates/Dialog.layout.latte',
            'renderModal' => $this->opened,
        ]);

        if ($this->template->getFile() === null) {
            throw new InvalidStateException('No template file has been set for dialog');
        }

        $this->template->render();
    }

    protected function show() : void
    {
        $this->opened = true;
        $this->redrawControl();
    }

    protected function hide() : void
    {
        $this->opened = false;
        $this->redrawControl();
    }
}
