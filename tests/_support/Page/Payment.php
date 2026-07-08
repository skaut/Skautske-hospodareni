<?php

declare(strict_types=1);

namespace Page;

use AcceptanceTester;
use DateTime;

use function date;
use function sprintf;

class Payment
{
    private const MODAL_NAME_INPUT = '#frm-paymentDialog-form-name';
    private const ADD_BUTTON_TOGGLE = '[data-test="payment-add-button-toggle"]';
    private const ADD_BUTTON_MENU = '[data-test="payment-add-button-menu"]';
    private const ADD_BUTTON_ITEM_GENERAL = '[data-test="payment-add-button-item-general"]';

    /** @var AcceptanceTester */
    private $tester;

    public function __construct(AcceptanceTester $tester)
    {
        $this->tester = $tester;
    }

    public function addPayment(string $name, ?string $email, float $amount): void
    {
        $I = $this->tester;

        $this->openPaymentDialog();
        $I->fillFieldStable(self::MODAL_NAME_INPUT, $name, 10, false);

        if ($email !== null) {
            $I->fillFieldStable('#frm-paymentDialog-form-email', $email, 10, false);
        }

        $I->fillFieldStable('#frm-paymentDialog-form-amount', (string) $amount, 10, false);
        $this->selectNextWorkdayForDueDate();
        $this->submitPayment();
        $I->waitForText($name, 20, '[data-test="payment-group-grid"]');
    }

    public function seeNumberOfPaymentsWithState(string $state, int $count): void
    {
        $this->tester->seeNumberOfElements("(//*[text()='$state'])", $count);
    }

    public function selectNextWorkdayForDueDate(): void
    {
        $I = $this->tester;
        $dayOfWeek = date('N');

        $daysToNextWorkday = $dayOfWeek < 5 ? 1 : 8 - $dayOfWeek;

        $date = (new DateTime())->modify(sprintf('+ %d days', $daysToNextWorkday))->format('d.m.Y');

        $I->fillFieldStable('#frm-paymentDialog-form-dueDate', $date, 10, false);
        $I->executeJS(
            'var input = document.querySelector("#frm-paymentDialog-form-dueDate");'
            .'if (!input) { return false; }'
            .'input.dispatchEvent(new Event("change", { bubbles: true }));'
            .'input.blur();'
            .'return true;',
        );
    }

    private function openPaymentDialog(): void
    {
        $I = $this->tester;

        $I->waitForElementClickable(self::ADD_BUTTON_TOGGLE, 10);
        $I->clickStable(self::ADD_BUTTON_TOGGLE);
        $I->waitForElementVisible(self::ADD_BUTTON_MENU, 10);
        $I->waitForElementVisible(self::ADD_BUTTON_ITEM_GENERAL, 10);
        $I->clickStable(self::ADD_BUTTON_ITEM_GENERAL);
        $I->waitForElementVisible(self::MODAL_NAME_INPUT, 15);
    }

    public function submitPayment(): void
    {
        $I = $this->tester;

        $I->executeJS('document.querySelector("#frm-paymentDialog-form input[name=\'send\']").click()');

        // Wait for AJAX to complete: modal closes and page becomes interactive again.
        $I->waitForJS(
            'var input = document.querySelector("#frm-paymentDialog-form-name");'
            .'return !input || input.offsetParent === null || input.getClientRects().length === 0;',
            15,
        );
        $I->waitForText('Platba byla přidána', 10);
        $I->waitForElementNotVisible('.modal-backdrop', 10);
        $I->waitForElementClickable('[data-test="payment-add-button-toggle"]', 10);
    }
}
