<?php
/**
 * This file is part of the AlesWita\FormRenderer
 * Copyright (c) 2017 Ales Wita (aleswita+github@gmail.com)
 */
declare(strict_types=1);

namespace App;

use Nette;
use Nette\Forms\Rendering\DefaultFormRenderer;

/**
 * @author Ales Wita
 * @license MIT
 */
class Bootstrap4FormRenderer extends DefaultFormRenderer
{
    /** @var array */
    public $wrappers = [
        'form' => [
            'container' => null,
        ],
        'error' => [
            'container' => 'div class="row mb-3"',
            'item' => 'div class="col-12 alert alert-danger"',
        ],
        'group' => [
            'container' => null,
            'label' => 'p class="h3 mt-4"',
            'description' => 'p class="pl-3 lead"',
        ],
        'controls' => [
            'container' => null,
        ],
        'pair' => [
            'container' => 'div class="form-group"',
            '.required' => null,
            '.optional' => null,
            '.odd' => null,
            '.error' => null,
        ],
        'control' => [
            'container' => null,
            '.odd' => null,
            'description' => 'small class="form-text text-muted"',
            'requiredsuffix' => null,
            'errorcontainer' => 'div class="invalid-feedback"',
            'erroritem' => null,
            '.required' => null,
            '.text' => null,
            '.password' => null,
            '.file' => null,
            '.email' => null,
            '.number' => null,
            '.submit' => null,
            '.image' => null,
            '.button' => null,
        ],
        'label' => [
            'container' => null,
            'suffix' => null,
            'requiredsuffix' => '*',
        ],
        'hidden' => [
            'container' => null,
        ],
    ];

    /**
     * @param Nette\Forms\IControl $control
     * @param bool $own
     * @return string
     */
    public function renderErrors(Nette\Forms\IControl $control = null, $own = true) : string
    {
        if ($control instanceof Nette\Forms\Controls\Checkbox || $control instanceof Nette\Forms\Controls\RadioList || $control instanceof Nette\Forms\Controls\UploadControl) {
            $temp = $this->wrappers['control']['errorcontainer'];
            $this->wrappers['control']['errorcontainer'] = $this->wrappers['control']['errorcontainer'] . ' style="display: block"';
        }
        $parent = parent::renderErrors($control, $own);
        if ($control instanceof Nette\Forms\Controls\Checkbox || $control instanceof Nette\Forms\Controls\RadioList || $control instanceof Nette\Forms\Controls\UploadControl) {
            $this->wrappers['control']['errorcontainer'] = $temp;
        }
        return $parent;
    }

    /**
     * @param array $controls
     * @return string
     */
    public function renderPairMulti(array $controls) : string
    {
        foreach ($controls as $control) {
            if ($control instanceof Nette\Forms\Controls\Button) {
                if ($control->controlPrototype->class === null || (is_array($control->controlPrototype->class) && ! Nette\Utils\Strings::contains(implode(' ',
                            array_keys($control->controlPrototype->class)), 'btn btn-'))) {
                    $control->controlPrototype->addClass((empty($primary) ? 'btn btn-outline-primary' : 'btn btn-outline-secondary'));
                }
                $primary = true;
            }
        }
        return parent::renderPairMulti($controls);
    }

    /**
     * @param Nette\Forms\IControl $control
     * @return Nette\Utils\Html
     */
    public function renderLabel(Nette\Forms\IControl $control) : Nette\Utils\Html
    {
        if ($control instanceof Nette\Forms\Controls\Checkbox || $control instanceof Nette\Forms\Controls\CheckboxList) {
            $control->labelPrototype->addClass('form-check-label');
        } elseif ($control instanceof Nette\Forms\Controls\RadioList) {
            $control->labelPrototype->addClass('form-check-label');
        } else {
        }
        $parent = parent::renderLabel($control);
        return $parent;
    }

    /**
     * @param Nette\Forms\IControl $control
     * @return Nette\Utils\Html
     */
    public function renderControl(Nette\Forms\IControl $control) : Nette\Utils\Html
    {
        if ($control instanceof Nette\Forms\Controls\Checkbox || $control instanceof Nette\Forms\Controls\CheckboxList) {
            $control->controlPrototype->addClass('form-check-input');
            if ($control instanceof Nette\Forms\Controls\CheckboxList) {
                $control->separatorPrototype->setName('div')->addClass('form-check form-check-inline');
            }
        } elseif ($control instanceof Nette\Forms\Controls\RadioList) {
            $control->containerPrototype->setName('div')->addClass('form-check');
            $control->itemLabelPrototype->addClass('form-check-label');
            $control->controlPrototype->addClass('form-check-input');
        } elseif ($control instanceof Nette\Forms\Controls\UploadControl) {
            $control->controlPrototype->addClass('form-control-file');
        } else {
            if ($control->hasErrors()) {
                $control->controlPrototype->addClass('is-invalid');
            }
            $control->controlPrototype->addClass('form-control');
        }
        $parent = parent::renderControl($control);
        // addons
        if ($control instanceof Nette\Forms\Controls\TextInput) {
            $leftAddon = $control->getOption('left-addon');
            $rightAddon = $control->getOption('right-addon');
            if ($leftAddon !== null || $rightAddon !== null) {
                $children = $parent->getChildren();
                $parent->removeChildren();
                $container = Nette\Utils\Html::el('div')->setClass('input-group');
                if ($leftAddon !== null) {
                    if (! is_array($leftAddon)) {
                        $leftAddon = [$leftAddon];
                    }
                    $div = Nette\Utils\Html::el('div')->setClass('input-group-prepend');
                    foreach ($leftAddon as $v) {
                        $div->insert(null, Nette\Utils\Html::el('span')->setClass('input-group-text')->setText($v));
                    }
                    $container->insert(null, $div);
                }
                foreach ($children as $child) {
                    $foo = Nette\Utils\Strings::after($child, $control->getControlPart()->render());
                    if ($foo !== false) {
                        $container->insert(null, $control->getControlPart()->render());
                        $description = $foo;
                    } else {
                        $container->insert(null, $child);
                    }
                }
                if ($rightAddon !== null) {
                    if (! is_array($rightAddon)) {
                        $rightAddon = [$rightAddon];
                    }
                    $div = Nette\Utils\Html::el('div')->setClass('input-group-append');
                    foreach ($rightAddon as $v) {
                        $div->insert(null, Nette\Utils\Html::el('span')->setClass('input-group-text')->setText($v));
                    }
                    $container->insert(null, $div);
                }
                $parent->insert(null, $container);
                if (! empty($description)) {
                    $parent->insert(null, $description);
                }
            }
        }
        return $parent;
    }
}
