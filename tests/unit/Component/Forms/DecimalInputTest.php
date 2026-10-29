<?php

declare(strict_types=1);

namespace Component\Forms;

use Codeception\Test\Unit;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Http\Request;
use Nette\Http\UrlScript;

final class DecimalInputTest extends Unit
{
    public function testRendersDecimalKeyboardAndPattern(): void
    {
        $html = (string) $this->consumption($this->submittedForm(''))->getControl();

        self::assertStringContainsString('inputmode="decimal"', $html);
        self::assertStringContainsString('pattern="\d+([,.]\d{1,2})?"', $html);
        self::assertStringContainsString('data-decimal', $html);
    }

    public function testCommaIsAcceptedAsDecimalSeparator(): void
    {
        $form = $this->submittedForm('6,2');

        self::assertTrue($form->isValid());
        self::assertSame(6.2, $this->consumption($form)->getValue());
    }

    public function testTextIsRejected(): void
    {
        $form = $this->submittedForm('abc');

        self::assertFalse($form->isValid());
        self::assertSame(['Spotřeba musí být číslo.'], $this->consumption($form)->getErrors());
    }

    private function submittedForm(string $consumption): Form
    {
        $form = new Form();
        $form->allowCrossOrigin();
        $form->httpRequest = new Request(new UrlScript('http://localhost/'), post: ['fields' => ['consumption' => $consumption]], method: 'POST');
        $fields = new BaseContainer();
        $form['fields'] = $fields;
        $fields->addDecimal('consumption', 'Spotřeba', 'Spotřeba musí být číslo.');

        return $form;
    }

    private function consumption(Form $form): TextInput
    {
        $control = $form->getComponent('fields-consumption');
        self::assertInstanceOf(TextInput::class, $control);

        return $control;
    }
}
