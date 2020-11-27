<?php

declare(strict_types=1);

namespace App;

use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Utils\Strings;
use function array_keys;
use function implode;
use function is_array;

// We cannot use typehints because of LSP
// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration

/**
 * Inspired by https://github.com/aleswita/FormRenderer
 */
class Bootstrap4FormRenderer extends DefaultFormRenderer
{
    /** @var mixed[] */
    public $wrappers = [
        'form' => ['container' => null],
        'error' => [
            'container' => 'div class="row mb-3"',
            'item' => 'div class="col-12 alert alert-danger"',
        ],
        'group' => [
            'container' => null,
            'label' => 'p class="h3 mt-4"',
            'description' => 'p class="pl-3 lead"',
        ],
        'controls' => ['container' => null],
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
        'hidden' => ['container' => null],
    ];

    /**
     * @param bool $own
     *
     * @return string
     */
    public function renderErrors(?Nette\Forms\IControl $control = null, $own = true) : string
    {
        if ($control instanceof Nette\Forms\Controls\Checkbox ||
            $control instanceof Nette\Forms\Controls\RadioList ||
            $control instanceof Nette\Forms\Controls\UploadControl) {
            $originalErrorContainer = $this->wrappers['control']['errorcontainer'];

            $this->wrappers['control']['errorcontainer'] = 'div class="invalid-feedback d-block"';

            $errors = parent::renderErrors($control, $own);

            $this->wrappers['control']['errorcontainer'] = $originalErrorContainer;

            return $errors;
        }

        return parent::renderErrors($control, $own);
    }

    /**
     * @param BaseControl[] $controls
     *
     * @return string
     */
    public function renderPairMulti(array $controls) : string
    {
        $primary = false;

        foreach ($controls as $control) {
            if (! $control instanceof Button) {
                continue;
            }

            $prototype = $control->getControlPrototype();
            $class     = $prototype->getAttribute('class') ?? [];

            if (is_array($class) && ! Strings::contains(implode(' ', array_keys($class)), 'btn btn-')) {
                $prototype->appendAttribute('class', $primary ? 'btn btn-outline-primary' : 'btn btn-outline-secondary');
            }

            $primary = true;
        }

        return parent::renderPairMulti($controls);
    }

    public function renderLabel(Nette\Forms\IControl $control) : Nette\Utils\Html
    {
        if ($control instanceof Nette\Forms\Controls\Checkbox || $control instanceof Nette\Forms\Controls\CheckboxList) {
            $control->labelPrototype->appendAttribute('class', 'form-check-label');
        } elseif ($control instanceof Nette\Forms\Controls\RadioList) {
            $control->labelPrototype->appendAttribute('class', 'form-check-label');
        }

        return parent::renderLabel($control);
    }

    /**
     * @param Nette\Forms\IControl $control
     *
     * @return Nette\Utils\Html
     */
    public function renderControl(Nette\Forms\IControl $control) : Nette\Utils\Html
    {
        if (! $control instanceof BaseControl) {
            return parent::renderControl($control);
        }

        $controlPrototype = $control->getControlPrototype();

        if ($control instanceof Nette\Forms\Controls\Checkbox) {
            $controlPrototype->appendAttribute('class', 'form-check-input');
        } elseif ($control instanceof Nette\Forms\Controls\CheckboxList) {
            $controlPrototype->appendAttribute('class', 'form-check-input');
            $control->separatorPrototype->setName('div')->appendAttribute('class', 'form-check form-check-inline');
        } elseif ($control instanceof Nette\Forms\Controls\RadioList) {
            $control->containerPrototype->setName('div')->appendAttribute('class', 'form-check');
            $control->itemLabelPrototype->addClass('form-check-label');
            $controlPrototype->appendAttribute('class', 'form-check-input');
        } elseif ($control instanceof Nette\Forms\Controls\UploadControl) {
            $controlPrototype->appendAttribute('class', 'form-control-file');
        } elseif ($control instanceof Nette\Forms\Controls\SelectBox) {
            $controlPrototype->appendAttribute('class', 'custom-select');
        } else {
            if ($control->hasErrors()) {
                $control->controlPrototype->appendAttribute('class', 'is-invalid');
            }
            $control->controlPrototype->appendAttribute('class', 'form-control');
        }

        return parent::renderControl($control);
    }
}
