<?php

declare(strict_types=1);

namespace Page;

use AcceptanceTester;
use DateTime;

use function date;
use function sprintf;

class Payment
{
    private const MODAL_FORM = '.modal.show #frm-paymentDialog-form';

    /** @var AcceptanceTester */
    private $tester;

    public function __construct(AcceptanceTester $tester)
    {
        $this->tester = $tester;
    }

    public function addPayment(string $name, ?string $email, float $amount): void
    {
        $I = $this->tester;

        // Open the dropdown and click "add general"
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);
        $I->waitForElementClickable('[data-test="payment-add-button-item-general"]', 10);
        $I->clickStable('[data-test="payment-add-button-item-general"]');

        // Wait for Bootstrap modal animation to complete and form to be rendered
        $I->waitForJS(
            'var m = document.querySelector(".modal.show");'
            .'return m && getComputedStyle(m).opacity === "1"'
            .' && m.querySelector("#frm-paymentDialog-form") !== null',
            15,
        );
        $I->wait(1); // Let CSS animation fully complete

        // Scope ALL selectors to .modal.show to avoid stale hidden duplicates
        $I->fillFieldStable(self::MODAL_FORM.' input[name="name"]', $name, 10, false);

        if ($email !== null) {
            $I->fillFieldStable(self::MODAL_FORM.' input[name="email"]', $email, 10, false);
        }

        $I->fillFieldStable(self::MODAL_FORM.' input[name="amount"]', (string) $amount, 10, false);
        $this->selectNextWorkdayForDueDate();
        $this->submitPayment();
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

        $date = (new DateTime())->modify(sprintf('+ %d days', $daysToNextWorkday))->format('d.m. Y');

        $I->fillFieldStable(self::MODAL_FORM.' input[name="dueDate"]', $date, 10, false);
        $I->click('.modal.show .modal-dialog'); // Close date picker
    }

    public function submitPayment(): void
    {
        $I = $this->tester;

        // Submit the form INSIDE the visible modal (not via form= attribute
        // which targets the first #frm-paymentDialog-form in DOM — could be a stale hidden one)
        $I->executeJS('document.querySelector(".modal.show #frm-paymentDialog-form input[name=send]").click()');

        // Wait for AJAX to complete: modal closes, flash appears
        $I->waitForJS('return document.querySelector(".modal.show") === null', 15);
        $I->waitForText('Platba byla přidána', 10);
        $I->waitForElementNotVisible('.modal-backdrop', 10);
        $I->waitForElementClickable('[data-test="payment-add-button-toggle"]', 10);
    }
}
