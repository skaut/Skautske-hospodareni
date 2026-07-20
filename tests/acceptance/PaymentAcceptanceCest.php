<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use Page\Payment;

use function random_int;
use function uniqid;

abstract class PaymentAcceptanceCest extends BaseAcceptanceCest
{
    protected const ACCEPTANCE_USER_ID = 2465;

    protected AcceptanceTester $I;
    protected Payment $page;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $this->page = new Payment($I);
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    protected function openPaymentSubtypeSelector(string $menuItemSelector, string $pageTitle, string $currentUrl): void
    {
        $I = $this->I;

        $this->runWithSkautisRetry(
            $I,
            function () use ($I, $menuItemSelector, $pageTitle): string {
                $I->amOnPage('/nastenka');
                $navigationState = $I->waitForElementOrSkautisConnectionError('[data-test="global-nav-payments"]');
                if ($navigationState !== AcceptanceTester::PAGE_STATE_EXPECTED) {
                    return $navigationState;
                }

                $I->clickStable('[data-test="global-nav-payments"]');
                $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
                $I->clickStable('[data-test="payment-nav-groups"]');
                $I->waitForText('Platební skupiny', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
                $I->clickStable('[data-test="create-button-toggle"]');
                $I->waitForElementVisible('[data-test="create-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
                $I->clickStable($menuItemSelector);
                $I->waitForText($pageTitle, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

                return AcceptanceTester::PAGE_STATE_EXPECTED;
            },
            'opening payment subtype selector '.$menuItemSelector,
            $pageTitle,
        );

        $I->seeInCurrentUrl($currentUrl);
    }

    protected function createGeneralPaymentGroup(string $groupName): void
    {
        $this->page->createGeneralPaymentGroup($groupName);
    }

    protected function createSubtypePaymentGroup(string $type, int $bankAccountId = 1): int
    {
        $I = $this->I;

        $groupId = $I->haveInDatabase('pa_group', [
            'groupType' => $type,
            'sisId' => 1000 + random_int(1, 100000),
            'name' => uniqid('Payment subtype '.$type.' ', true),
            'amount' => 100.0,
            'due_date' => ChronosDate::today()->format('Y-m-d'),
            'constant_symbol' => null,
            'next_variable_symbol' => null,
            'state' => 'open',
            'note' => 'acceptance',
            'created_at' => '2026-01-01 00:00:00',
            'last_pairing' => '2026-01-01 00:00:00',
            'smtp_id' => null,
            'bank_account_id' => $bankAccountId,
            'oauth_id' => null,
            'is_reminders_enabled' => 0,
        ]);

        $I->haveInDatabase('pa_group_unit', [
            'group_id' => $groupId,
            'unit_id' => AcceptanceTester::UNIT_ID,
        ]);

        $I->haveInDatabase('pa_group_email', [
            'group_id' => $groupId,
            'type' => 'payment_info',
            'template_subject' => 'Acceptance',
            'template_body' => 'Acceptance body',
            'enabled' => 1,
        ]);

        return $groupId;
    }
}
