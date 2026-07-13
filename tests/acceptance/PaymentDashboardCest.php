<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use PHPUnit\Framework\Assert;

use function random_int;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentDashboardCest extends PaymentAcceptanceCest
{
    /** @group payment */
    public function dashboardPaymentGroupTileShowsCountsAndLinksToDetail(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');

        foreach (['preparing', 'preparing', 'preparing', 'completed', 'completed'] as $index => $state) {
            $I->haveInDatabase('pa_payment', [
                'group_id' => $groupId,
                'name' => 'Dashboard platba '.($index + 1),
                'amount' => 100,
                'due_date' => ChronosDate::today()->format('Y-m-d'),
                'variable_symbol' => (string) (910000 + $index),
                'constant_symbol' => null,
                'note' => '',
                'state' => $state,
            ]);
        }

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->amOnPage('/platby');
        $I->waitForElementVisible('[data-test="dashboard-group-card-'.$groupId.'"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $card = '[data-test="dashboard-group-card-'.$groupId.'"]';
        $I->seeElement($card.' .badge.text-bg-success');
        $I->see('5 celkem', $card.' [data-test="dashboard-group-stats-'.$groupId.'"]');
        $I->see('2 zaplaceno', $card.' [data-test="dashboard-group-stats-'.$groupId.'"]');
        $I->seeElement($card.' [data-test="dashboard-group-link-'.$groupId.'"].stretched-link');

        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/platby',
            $I->grabAttributeFrom('[data-test="dashboard-group-link-'.$groupId.'"]', 'href'),
        );

        $headingOrderIsCorrect = $I->executeJS(
            'const heading = document.querySelector(\'[data-test="dashboard-group-heading-'.$groupId.'"]\');'
            .'return heading.children[0].classList.contains("badge") && heading.children[1].tagName === "H3";',
        );
        Assert::assertTrue($headingOrderIsCorrect);

        $I->executeJS(
            'const card = document.querySelector(\'[data-test="dashboard-group-card-'.$groupId.'"]\');'
            .'const rect = card.getBoundingClientRect();'
            .'document.elementFromPoint(rect.right - 20, rect.top + 20).click();',
        );
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/skupiny/'.$groupId.'/platby');
        $I->seeInDatabase('payment_group_visit', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'group_id' => $groupId,
        ]);
    }

    /** @group payment */
    public function invoiceSequenceTilesKeepActionsAboveFullCardLinks(): void
    {
        $I = $this->I;
        $I->haveInDatabase('invoice_access_user', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'created_at' => '2026-06-18 12:00:00',
        ]);
        $sequenceNumber = random_int(100000, 999999);
        $sequenceId = $I->haveInDatabase('invoice_sequence', [
            'bank_account_id' => null,
            'unit' => AcceptanceTester::UNIT_ID,
            'sequence_id' => $sequenceNumber,
            'sequence' => 'NAV'.$sequenceNumber,
            'first_number' => '00001',
            'year' => (int) ChronosDate::today()->format('Y'),
            'description' => 'Navigační test fakturační řady',
            'oauth_id' => null,
            'default_due_date' => 14,
            'automatic_pairing_enabled' => 0,
            'pairing_days_back' => null,
            'last_pairing' => null,
            'state' => 'open',
            'phone' => null,
        ]);

        $I->amOnPage('/platby');
        $dashboardCard = '[data-test="dashboard-sequence-card-'.$sequenceId.'"]';
        $I->waitForElementVisible($dashboardCard, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement($dashboardCard.'.navigation-card');
        $I->seeElement('[data-test="dashboard-sequence-link-'.$sequenceId.'"].stretched-link');

        $I->clickStable('[data-test="dashboard-sequence-settings-'.$sequenceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-edit-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->amOnPage('/platby');
        $I->waitForElementVisible('[data-test="payments-link-invoices"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payments-link-invoices"]');
        $I->waitForElementVisible('[data-test="invoice-home"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $invoiceCard = '[data-test="invoice-sequence-card-'.$sequenceId.'"]';
        $I->seeElement($invoiceCard.'.navigation-card');
        $I->seeElement('[data-test="invoice-sequence-link-'.$sequenceId.'"].stretched-link');
        $I->executeJS(
            'const card = document.querySelector(\''.$invoiceCard.'\');'
            .'const rect = card.getBoundingClientRect();'
            .'document.elementFromPoint(rect.right - 20, rect.top + 20).click();',
        );
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @group settings */
    public function openSystemSettingsFromPaymentUtilityNavigation(): void
    {
        $I = $this->I;

        $I->wantTo('open system settings from the utility navigation on the payment dashboard');

        $I->clickStable('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby');
        $I->seeElement('[data-test="payment-nav-overview"]');
        $I->seeElement('[data-test="payments-card-groups"].navigation-card');
        $I->seeElement('[data-test="payments-link-groups"].stretched-link');
        Assert::assertSame('/platby/skupiny', $I->grabAttributeFrom('[data-test="payments-link-groups"]', 'href'));
        $I->seeElement('[data-test="payments-card-invoices"].navigation-card');
        $I->seeElement('[data-test="payments-link-invoices"].stretched-link');
        $I->dontSeeElement('[data-test="payments-card-settings"]');
        $I->waitForElementVisible('[data-test="utility-nav-settings"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="utility-nav-settings"]');

        $I->waitForText('Nastavení', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/nastaveni');
    }

    /** @group payment */
    public function helpPanelToggleOnGroupCreate(): void
    {
        $I = $this->I;

        $I->wantTo('toggle help panel on payment group create page');

        $I->clickStable('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Sidebar and content visible initially
        $I->seeElement('[data-test="help-sidebar"]');
        $I->seeElement('[data-test="help-content"]');
        $I->seeElement('[data-test="help-toggle"]');

        // Collapse
        $I->clickStable('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "true"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $collapsed = $I->executeJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed');
        Assert::assertSame('true', $collapsed);

        // Expand
        $I->clickStable('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "false"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $expanded = $I->executeJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed');
        Assert::assertSame('false', $expanded);
    }
}
