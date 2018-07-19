<?php

declare(strict_types=1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @link https://github.com/nextras/forms
 */

namespace App;

use Nette;
use Nette\Forms\Controls;
use Nette\Forms\Form;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Utils\Html;

/**
 * FormRenderer for Bootstrap 3 framework.
 */
class FormRenderer extends DefaultFormRenderer
{
    /** @var Controls\Button|NULL */
    public $primaryButton = null;

    /** @var bool */
    private $controlsInit = false;

    /** @var bool */
    private $inline = false;

    public function __construct(bool $inline = false)
    {
        if ($inline) {
            $this->wrappers['controls']['container']     = null;
            $this->wrappers['pair']['container']         = null;
            $this->wrappers['pair']['.error']            = null;
            $this->wrappers['control']['container']      = null;
            $this->wrappers['label']['container']        = null;
            $this->wrappers['control']['description']    = null;
            $this->wrappers['control']['errorcontainer'] = null;

            $this->inline = $inline;
        } else {
            $this->wrappers['controls']['container']     = null;
            $this->wrappers['pair']['container']         = 'div class=form-group';
            $this->wrappers['pair']['.error']            = 'has-error';
            $this->wrappers['control']['container']      = 'div class=col-sm-9';
            $this->wrappers['label']['container']        = 'div class="col-sm-3 control-label"';
            $this->wrappers['control']['description']    = 'span class=help-block';
            $this->wrappers['control']['errorcontainer'] = 'span class=help-block';
        }
    }


    public function renderBegin() : string
    {
        $this->controlsInit();
        return parent::renderBegin();
    }


    public function renderEnd() : string
    {
        $this->controlsInit();
        return parent::renderEnd();
    }


    public function renderBody() : string
    {
        $this->controlsInit();
        return parent::renderBody();
    }

    /**
     * @param Nette\Forms\Container|Nette\Forms\ControlGroup $parent
     */
    public function renderControls($parent) : string
    {
        $this->controlsInit();
        return parent::renderControls($parent);
    }


    public function renderPair(Nette\Forms\IControl $control) : string
    {
        $this->controlsInit();
        return parent::renderPair($control);
    }

    /**
     * @param  Nette\Forms\IControl[] $controls
     */
    public function renderPairMulti(array $controls) : string
    {
        $this->controlsInit();
        return parent::renderPairMulti($controls);
    }


    public function renderLabel(Nette\Forms\IControl $control) : Html
    {
        $this->controlsInit();
        return parent::renderLabel($control);
    }


    public function renderControl(Nette\Forms\IControl $control) : Html
    {
        $this->controlsInit();
        return parent::renderControl($control);
    }

    private function controlsInit() : void
    {
        if ($this->controlsInit) {
            return;
        }

        $this->controlsInit = true;
        $this->form->getElementPrototype()->addClass($this->inline ? 'form-inline' : 'form-horizontal');
        foreach ($this->form->getControls() as $control) {
            if ($control instanceof Controls\Button) {
                $markAsPrimary = $control === $this->primaryButton || (! isset($this->primaryButton) && empty($usedPrimary) && $control->parent instanceof Form);
                if ($markAsPrimary) {
                    $class       = 'btn btn-primary';
                    $usedPrimary = true;
                } else {
                    $class = 'btn btn-default';
                }
                $control->getControlPrototype()->addClass($class);
            } elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control');
            } elseif ($control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
                if ($control->getSeparatorPrototype()->getName() !== '') {
                    $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
                } else {
                    $control->getItemLabelPrototype()->addClass($control->getControlPrototype()->type . '-inline');
                }
            } elseif ($control instanceof Controls\Checkbox) {
                $control->getLabelPrototype()->addClass($control->getControlPrototype()->type . '-inline');
            }
        }
    }
}
