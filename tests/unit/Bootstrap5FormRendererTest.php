<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;
use Nette\Forms\Form;

/**
 * Renderer, kterým prochází každý formulář v aplikaci — testuje se přes reálně vyrenderované HTML.
 */
final class Bootstrap5FormRendererTest extends Unit
{
    public function testTextInputsGetFormControlClassesAndLabels(): void
    {
        $form = $this->createForm();
        $form->addText('name', 'Jméno')->setRequired();
        $form->addPassword('password', 'Heslo');
        $form->addUpload('scan', 'Sken');
        $form->addSelect('unit', 'Jednotka', [1 => 'Středisko']);

        $html = $form->__toString();

        self::assertStringContainsString('class="form-label"', $html);
        self::assertStringContainsString('class="form-control"', $html);
        self::assertStringContainsString('class="form-control-file"', $html);
        self::assertStringContainsString('class="form-select"', $html);
        self::assertStringContainsString('<div class="mb-3">', $html, 'pár label/input je zabalený s odsazením');
        self::assertStringContainsString('*', $html, 'povinné pole má hvězdičku');
    }

    public function testCheckboxAndRadioGetFormCheckClasses(): void
    {
        $form = $this->createForm();
        $form->addCheckbox('agree', 'Souhlasím');
        $form->addCheckboxList('units', 'Jednotky', [1 => 'Středisko', 2 => 'Oddíl']);
        $form->addRadioList('type', 'Typ', [1 => 'Příjem', 2 => 'Výdaj']);

        $html = $form->__toString();

        self::assertStringContainsString('class="form-check-label"', $html);
        self::assertStringContainsString('class="form-check-input"', $html);
        self::assertStringContainsString('form-check form-check-inline', $html, 'checkbox list se řadí do řádku');
    }

    public function testButtonsGetPrimaryAndSecondaryClasses(): void
    {
        $form = $this->createForm();
        $form->addSubmit('send', 'Uložit');
        $form->addSubmit('cancel', 'Zrušit');
        $form->addSubmit('custom', 'Vlastní')->getControlPrototype()->appendAttribute('class', 'btn btn-danger');

        $html = $form->__toString();

        self::assertStringContainsString('btn btn-primary', $html, 'první tlačítko je primární');
        self::assertStringContainsString('btn btn-outline-secondary', $html, 'další tlačítka jsou sekundární');
        self::assertStringContainsString('btn btn-danger', $html, 'vlastní btn- třída se nepřepisuje');
        self::assertStringNotContainsString('btn btn-danger btn-outline-secondary', $html);
    }

    public function testInvalidTextInputGetsIsInvalidClassAndFeedbackContainer(): void
    {
        $form = $this->createForm();
        $control = $form->addText('name', 'Jméno');
        $control->addError('Musíte vyplnit jméno.');

        $html = $form->__toString();

        self::assertStringContainsString('is-invalid', $html);
        self::assertStringContainsString('class="invalid-feedback"', $html);
        self::assertStringContainsString('Musíte vyplnit jméno.', $html);
    }

    public function testCheckboxErrorsUseBlockFeedbackAndDoNotLeakToOtherControls(): void
    {
        $form = $this->createForm();
        $checkbox = $form->addCheckbox('agree', 'Souhlasím');
        $checkbox->addError('Musíte souhlasit.');
        $text = $form->addText('name', 'Jméno');
        $text->addError('Musíte vyplnit jméno.');

        $renderer = $form->getRenderer();
        self::assertInstanceOf(Bootstrap5FormRenderer::class, $renderer);

        self::assertStringContainsString('invalid-feedback d-block', $renderer->renderErrors($checkbox));
        self::assertStringNotContainsString('d-block', $renderer->renderErrors($text), 'nastavení se po checkboxu vrací zpět');
    }

    public function testFormErrorsAreRenderedInAlertContainer(): void
    {
        $form = $this->createForm();
        $form->addText('name', 'Jméno');
        $form->addError('Formulář nelze odeslat.');

        $html = $form->__toString();

        self::assertStringContainsString('alert alert-danger', $html);
        self::assertStringContainsString('Formulář nelze odeslat.', $html);
    }

    public function testGroupLabelUsesHeadingWrapper(): void
    {
        $form = $this->createForm();
        $form->addGroup('Základní údaje');
        $form->addText('name', 'Jméno');

        $html = $form->__toString();

        self::assertStringContainsString('<p class="h3 mt-4">Základní údaje</p>', $html);
    }

    private function createForm(): Form
    {
        $form = new Form();
        $form->setRenderer(new Bootstrap5FormRenderer());

        return $form;
    }
}
