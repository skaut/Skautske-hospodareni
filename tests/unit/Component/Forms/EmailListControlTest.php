<?php

declare(strict_types=1);

namespace Component\Forms;

use Codeception\Test\Unit;
use Nette\Forms\Form;
use Nette\Http\Request;
use Nette\Http\UrlScript;

final class EmailListControlTest extends Unit
{
    public function testCheckedAddressesAndTheTypedOneBecomeTheValue(): void
    {
        $form = $this->submittedForm([
            'selected' => ['matka@example.com', ' jan@example.com ', 'matka@example.com'],
            'new' => 'treti@example.com',
        ]);
        $control = $form['emails'];
        self::assertInstanceOf(EmailListControl::class, $control);

        self::assertTrue($form->isValid());
        self::assertSame(['matka@example.com', 'jan@example.com', 'treti@example.com'], $control->getValue());
        self::assertSame(['emails' => ['matka@example.com', 'jan@example.com', 'treti@example.com']], $form->getValues('array'));
    }

    public function testNothingCheckedAndNothingTypedIsAnEmptyList(): void
    {
        $form = $this->submittedForm(['new' => '']);
        $control = $form['emails'];
        self::assertInstanceOf(EmailListControl::class, $control);

        self::assertTrue($form->isValid());
        self::assertSame([], $control->getValue());
        self::assertFalse($control->isFilled());
    }

    public function testInvalidAddressIsRejected(): void
    {
        $form = $this->submittedForm(['selected' => ['jan@example.com'], 'new' => 'neni-email']);
        $control = $form['emails'];
        self::assertInstanceOf(EmailListControl::class, $control);

        self::assertFalse($form->isValid());
        self::assertSame(["E-mail 'neni-email' nemá platný formát."], $control->getErrors());
    }

    public function testRendersOfferedAddressesWithSelectionAndTheOthersWithFallbackLabel(): void
    {
        $form = new Form();
        $form->httpRequest = new Request(new UrlScript('http://localhost/'), method: 'GET');
        $control = new EmailListControl('E-mail', 'Zadáno ručně');
        $control->setOffered(['jan@example.com' => 'Hlavní', 'matka@example.com' => 'Matka']);
        $form['emails'] = $control;
        $control->setDefaultValue(['matka@example.com', 'jiny@example.com']);

        $html = (string) $control->getControl();

        self::assertStringContainsString(
            '<input type="checkbox" class="form-check-input" name="emails[selected][]" id="frm-emails-0" value="jan&#64;example.com">'
            .'<label class="form-check-label" for="frm-emails-0">jan@example.com <span class="text-body-secondary">(Hlavní)</span></label>',
            $html,
        );
        self::assertStringContainsString(
            '<input type="checkbox" class="form-check-input" name="emails[selected][]" id="frm-emails-1" value="matka&#64;example.com" checked>'
            .'<label class="form-check-label" for="frm-emails-1">matka@example.com <span class="text-body-secondary">(Matka)</span></label>',
            $html,
        );
        self::assertStringContainsString(
            '<input type="checkbox" class="form-check-input" name="emails[selected][]" id="frm-emails-2" value="jiny&#64;example.com" checked>'
            .'<label class="form-check-label" for="frm-emails-2">jiny@example.com <span class="text-body-secondary">(Zadáno ručně)</span></label>',
            $html,
        );
        self::assertStringContainsString('<input type="email" class="form-control" name="emails[new]" id="frm-emails-new"', $html);
        self::assertStringContainsString('data-email-list-name="emails[selected][]" data-email-list-id="frm-emails" data-email-list-fallback-label="Zadáno ručně"', $html);
        self::assertSame('<label>E-mail</label>', (string) $control->getLabel());
    }

    /** @param array<string, mixed> $emails the POST data under the control's name */
    private function submittedForm(array $emails): Form
    {
        $form = new Form();
        $form->allowCrossOrigin();
        $form->httpRequest = new Request(new UrlScript('http://localhost/'), post: ['emails' => $emails], method: 'POST');
        $form['emails'] = new EmailListControl('E-mail', 'Zadáno ručně');

        return $form;
    }
}
