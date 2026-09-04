<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use PHPUnit\Framework\Assert;

use function rawurlencode;
use function uniqid;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentDetailCest extends PaymentAcceptanceCest
{
    /** @group payment */
    public function openPaymentGroupOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium URL ', true);

        $I->wantTo('open a payment group on canonical url');

        $this->createGeneralPaymentGroup($groupName);
        $groupId = $I->grabFromDatabase('pa_group', 'id', ['name' => $groupName]);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-group-detail-'.$groupId.'"]');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText($groupName, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
    }

    /** @group payment */
    public function searchPaymentGroupsByName(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Search ', true);

        $I->wantTo('search payment groups by name');

        $this->createGeneralPaymentGroup($groupName);
        $groupId = $I->grabFromDatabase('pa_group', 'id', ['name' => $groupName]);

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForElementVisible('[data-test="payments-groups-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible(
            '[data-test="payment-group-detail-'.$groupId.'"]',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        // The search box is rendered only through the grid's outer filter row, so it
        // disappears silently when the grid loses setOuterFilterRendering(true).
        $I->seeElement('[data-test="datagrid-filter-search"]');

        // A single nonsense token: the grid filter splits a phrase into words and
        // matches any of them, so a two-word term would hit unrelated groups.
        $I->amOnPage('/platby/skupiny?grid-filter%5Bsearch%5D='.rawurlencode(uniqid('nenajdese', true)));
        $I->waitForText('Nenalezeny žádné záznamy.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeElement('[data-test="payment-group-detail-'.$groupId.'"]');

        $I->amOnPage('/platby/skupiny?grid-filter%5Bsearch%5D='.rawurlencode($groupName));
        $I->waitForElementVisible(
            '[data-test="payment-group-detail-'.$groupId.'"]',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );
        $I->seeElement('[data-test="datagrid-filter-search"]');
    }

    /** @group payment */
    public function openPaymentGroupEditOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Edit URL ', true);

        $I->wantTo('open payment group edit on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->clickStable('[data-test="payment-group-edit-link"]');
        $I->waitForElementVisible('[data-test="payment-group-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/upravit(?:\?.*)?$~');
        $I->waitForText('Editace skupiny: '.$groupName, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @group payment */
    public function openPaymentRepaymentsOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Repayment URL ', true);

        $I->wantTo('open payment repayments on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->clickStable('[data-test="payment-group-repayments-link"]');
        $I->waitForElementVisible('[data-test="payment-repayments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Vratky', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/vratky(?:\?.*)?$~');
    }

    /** @group payment */
    public function openPaymentMassAddOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium MassAdd URL ', true);

        $I->wantTo('open payment mass add on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-add-button-item-member"]');
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Přidat osoby z jednotky', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/osoby(?:\?.*)?$~');
    }

    /** @group payment */
    public function massAddSelectsEmailsByContactType(): void
    {
        $I = $this->I;

        $this->createGeneralPaymentGroup(uniqid('Selenium MassAdd emails ', true));
        $I->seeElement('[data-test="payment-group-detail-page"] .page-heading .badge.text-bg-success');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="payment-add-button-item-member"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $massAddUrl = (string) $I->grabAttributeFrom('[data-test="payment-add-button-item-member"]', 'href');
        Assert::assertMatchesRegularExpression('~^/platby/skupiny/\d+/osoby$~', $massAddUrl);
        $I->amOnPage($massAddUrl);
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        foreach (['main', 'other', 'father', 'mother'] as $emailType) {
            $I->seeElement('[data-test="mass-email-type-'.$emailType.'"]');
        }

        $summary = $I->executeJS(<<<'JS'
            const options = Array.from(document.querySelectorAll('[data-mass-email-select] option'));
            return {
                selectCount: document.querySelectorAll('[data-mass-email-select]').length,
                mainCount: options.filter(option => option.dataset.emailType === 'main').length,
                otherCount: options.filter(option => option.dataset.emailType === 'other').length,
                unselectedMainCount: options.filter(option => option.dataset.emailType === 'main' && !option.selected).length,
                selectedNonMainCount: options.filter(option => option.dataset.emailType !== 'main' && option.selected).length,
                availableBulkTypes: ['other', 'father', 'mother'].filter(
                    type => options.some(option => option.dataset.emailType === type),
                ),
            };
            JS);

        Assert::assertGreaterThan(0, $summary['selectCount']);
        Assert::assertGreaterThan(0, $summary['mainCount']);
        Assert::assertGreaterThan(0, $summary['otherCount']);
        Assert::assertSame(0, $summary['unselectedMainCount']);
        Assert::assertSame(0, $summary['selectedNonMainCount']);
        Assert::assertNotEmpty($summary['availableBulkTypes']);

        $emailType = $summary['availableBulkTypes'][0];
        $toggle = '[data-test="mass-email-type-'.$emailType.'"]';
        $I->clickStable($toggle);
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\'))'
            .'.every(option => option.selected);',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        $I->clickStable($toggle);
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\'))'
            .'.every(option => !option.selected);',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        $manuallySelected = $I->executeJS(
            'const option = document.querySelector(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\');'
            .'option.selected = true;'
            .'option.parentElement.dispatchEvent(new Event("change", {bubbles: true}));'
            .'return option.selected;',
        );

        Assert::assertTrue($manuallySelected);
        $I->dontSeeCheckboxIsChecked($toggle);
    }

    /** @group payment */
    /** @group payment */
    public function participantAddLinkUsesCanonicalUrlForSubtypeGroups(): void
    {
        $I = $this->I;

        $I->wantTo('see canonical participant add link for camp, event and education payment groups');

        foreach (['camp', 'event', 'education'] as $type) {
            $groupId = $this->createSubtypePaymentGroup($type);

            $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
            $I->waitForText('Přidat platbu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
            $I->clickStable('[data-test="payment-add-button-toggle"]');
            $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

            Assert::assertSame(
                '/platby/skupiny/'.$groupId.'/ucastnici',
                $I->grabAttributeFrom('[data-test="payment-add-button-item-participant"]', 'href'),
            );
        }
    }

    /** @group payment */
    public function registrationAddLinkUsesCanonicalGroupUrl(): void
    {
        $I = $this->I;

        $I->wantTo('see group-centric registration add link in payment group detail');

        $groupId = $this->createSubtypePaymentGroup('registration');

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForText('Přidat platbu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/osoby',
            $I->grabAttributeFrom('[data-test="payment-add-button-item-registration"]', 'href'),
        );
    }

    /** @group payment */
    public function registrationJournalUsesCanonicalGroupUrl(): void
    {
        $I = $this->I;

        $I->wantTo('see a group-centric canonical journal link in registration payment group detail');

        $groupId = $this->createSubtypePaymentGroup('registration');

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForText('Přidat platbu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/casopisy',
            $I->grabAttributeFrom('[data-test="payment-group-journal-link"]', 'href'),
        );
    }

    /** @group payment */
    public function paymentEmailActionIsDisabledWithoutSender(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $paymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba bez odesílatele',
            'amount' => 500,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '900001',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
        $I->haveInDatabase('pa_payment_email_recipients', [
            'payment_id' => $paymentId,
            'email_address' => 'recipient@example.com',
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $selector = '[data-test="payment-email-action-'.$paymentId.'"]';
        $I->seeElement($selector.'.btn-light.disabled');
        $I->seeElement($selector.'[title="Skupina nemá nastavený e-mail odesílatele"]');
        $I->dontSeeElement($selector.'.ui--sendEmail');
    }

    /** @group payment */
    public function paymentGroupBankAccountOverviewIsScopedAndPairsItsPayment(): void
    {
        $I = $this->I;
        $bankAccountId = $I->haveInDatabase('pa_bank_account', [
            'unit_id' => AcceptanceTester::UNIT_ID,
            'name' => 'Účet přehledu skupiny',
            'token' => null,
            'transaction_source' => 'gpc',
            'created_at' => '2026-06-18 12:00:00',
            'allowed_for_subunits' => 1,
            'number_prefix' => null,
            'number_number' => '2000942146',
            'number_bank_code' => '2010',
        ]);
        $groupId = $this->createSubtypePaymentGroup('event', $bankAccountId);
        $otherGroupId = $this->createSubtypePaymentGroup('event', $bankAccountId);
        $paymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba aktuální skupiny',
            'amount' => 250,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '220001',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
        $I->haveInDatabase('pa_payment', [
            'group_id' => $otherGroupId,
            'name' => 'Platba jiné skupiny',
            'amount' => 250,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '220001',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
        $manualPaymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba pro ruční párování',
            'amount' => 150,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '220003',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
        $transactionKey = 'acceptance-group-payment-'.$groupId;
        $manualTransactionKey = 'acceptance-group-manual-'.$groupId;
        $transactionDate = ChronosDate::today()->format('Y-m-d').' 12:00:00';
        $I->haveInDatabase('bank_transaction', [
            'bank_account_id' => $bankAccountId,
            'import_batch_id' => null,
            'source' => 'fio',
            'transaction_key' => $transactionKey,
            'source_transaction_id' => 'source-'.$transactionKey,
            'date' => $transactionDate,
            'amount' => 250,
            'counter_account' => '123456789/2010',
            'counter_name' => 'Příjem pro skupinu',
            'variable_symbol' => 220001,
            'constant_symbol' => null,
            'note' => 'Bankovní úhrada',
            'imported_at' => $transactionDate,
        ]);
        $I->haveInDatabase('bank_transaction', [
            'bank_account_id' => $bankAccountId,
            'import_batch_id' => null,
            'source' => 'fio',
            'transaction_key' => $manualTransactionKey,
            'source_transaction_id' => 'source-'.$manualTransactionKey,
            'date' => $transactionDate,
            'amount' => 150,
            'counter_account' => '123456789/2010',
            'counter_name' => 'Příjem pro ruční párování',
            'variable_symbol' => null,
            'constant_symbol' => null,
            'note' => 'Bankovní úhrada bez VS',
            'imported_at' => $transactionDate,
        ]);
        $I->haveInDatabase('bank_transaction', [
            'bank_account_id' => $bankAccountId,
            'import_batch_id' => null,
            'source' => 'fio',
            'transaction_key' => 'acceptance-group-outgoing-'.$groupId,
            'source_transaction_id' => 'source-outgoing-'.$groupId,
            'date' => $transactionDate,
            'amount' => -250,
            'counter_account' => '123456789/2010',
            'counter_name' => 'Odchozí platba',
            'variable_symbol' => 220001,
            'constant_symbol' => null,
            'note' => 'Odchozí bankovní pohyb',
            'imported_at' => $transactionDate,
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-bank-account-toggle"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->resizeWindow(1440, 900);
        $headingLayout = $I->executeJS(<<<'JS'
const heading = document.querySelector('[data-test="payment-group-detail-page"] .page-heading');
const body = heading?.querySelector(':scope > .card-body');
const title = heading?.querySelector('h1');
const actions = heading?.querySelector('.page-heading-actions');
const lead = heading?.querySelector('.page-lead');
const style = body === null ? null : getComputedStyle(body);

return {
    titleTop: title?.getBoundingClientRect().top ?? null,
    actionsTop: actions?.getBoundingClientRect().top ?? null,
    titleBottom: title?.getBoundingClientRect().bottom ?? null,
    actionsBottom: actions?.getBoundingClientRect().bottom ?? null,
    leadTop: lead?.getBoundingClientRect().top ?? null,
    leadWidth: Math.round(lead?.getBoundingClientRect().width ?? 0),
    bodyContentWidth: Math.round((body?.clientWidth ?? 0) - Number.parseFloat(style?.paddingLeft ?? '0') - Number.parseFloat(style?.paddingRight ?? '0')),
};
JS);
        Assert::assertSame($headingLayout['titleTop'], $headingLayout['actionsTop']);
        Assert::assertGreaterThanOrEqual(
            max($headingLayout['titleBottom'], $headingLayout['actionsBottom']),
            $headingLayout['leadTop'],
        );
        Assert::assertSame($headingLayout['bodyContentWidth'], $headingLayout['leadWidth']);

        $I->resizeWindow(375, 900);
        $mobileHeadingLayout = $I->executeJS(<<<'JS'
const heading = document.querySelector('[data-test="payment-group-detail-page"] .page-heading');
const actions = heading?.querySelector('.page-heading-actions');
const lead = heading?.querySelector('.page-lead');

return {
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    actionsBottom: actions?.getBoundingClientRect().bottom ?? null,
    leadTop: lead?.getBoundingClientRect().top ?? null,
};
JS);
        Assert::assertSame(0, $mobileHeadingLayout['overflow']);
        Assert::assertGreaterThanOrEqual($mobileHeadingLayout['actionsBottom'], $mobileHeadingLayout['leadTop']);
        $I->see('Zobrazit bankovní platby', '[data-test="payment-group-bank-account-toggle"]');
        $I->seeElement('[data-test="payment-group-bank-account-toggle"].ajax.btn-light.btn-sm');
        $I->dontSeeElement('[data-test="payment-group-bank-account-transactions"]');
        $I->seeElement('[data-test="pair-button-main"].ajax');

        $I->clickStable('[data-test="payment-group-bank-account-toggle"]');
        $I->waitForElementVisible('[data-test="payment-group-bank-account-transactions"] .datagrid', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Skrýt bankovní platby', '[data-test="payment-group-bank-account-toggle"]');
        $I->see('Účet přehledu skupiny', '[data-test="payment-group-bank-account-transactions"]');
        $I->see('Příjem pro skupinu', '[data-test="payment-group-bank-account-transactions"]');
        $I->dontSee('Odchozí platba', '[data-test="payment-group-bank-account-transactions"]');
        $I->see('Platba aktuální skupiny', '[data-test="payment-group-bank-account-transactions"]');
        $I->dontSee('Platba jiné skupiny', '[data-test="payment-group-bank-account-transactions"]');
        $I->see('Nespárovaná platba odpovídá více platebním skupinám', '[data-test="payment-group-bank-account-transactions"]');
        $I->seeElement('[data-test="payment-group-bank-account-transactions"] #frm-bankAccountTransactionsGrid-filter-filter-search');
        $I->seeElement('[data-test="payment-group-bank-account-transactions"] #datagrid-sort-date');
        $I->seeElement('[data-test="payment-group-bank-account-transactions"] .btn-outline-success.ajax');

        $I->clickStable('[data-test="payment-group-bank-account-toggle"]');
        $I->waitForText('Zobrazit bankovní platby', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-group-bank-account-toggle"]');
        $I->dontSeeElement('[data-test="payment-group-bank-account-transactions"]');
        Assert::assertStringNotContainsString('bankAccountTransactionsLoaded', $I->grabFromCurrentUrl());
        Assert::assertStringNotContainsString('bankAccountPaymentId', $I->grabFromCurrentUrl());

        $I->clickStable('[data-test="payment-group-bank-account-toggle"]');
        $I->waitForElementVisible('[data-test="payment-group-bank-account-transactions"] .datagrid', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->disablePopups();
        $I->clickStable('[data-test="payment-group-bank-account-transactions"] .btn-outline-success');
        $I->waitForText('Bankovní transakce byla ručně spárována s platbou.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('bank_transaction_pairing', [
            'transaction_key' => $manualTransactionKey,
            'payment_id' => $manualPaymentId,
        ]);
        $I->seeInDatabase('pa_payment', ['id' => $manualPaymentId, 'state' => 'completed']);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
    }

    /** @group payment */
    public function paymentGroupWithoutBankAccountDisablesBankAccountOverview(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $I->updateInDatabase('pa_group', ['bank_account_id' => null], ['id' => $groupId]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->seeElement('[data-test="payment-group-bank-account-toggle-disabled"].disabled[aria-disabled="true"]');
        $I->seeElement('[title="Není připojený žádný bankovní účet."][data-bs-toggle="tooltip"]');
        $I->dontSeeElement('[data-test="payment-group-bank-account-transactions"]');
    }

    /** @group payment */
    public function paymentGridRendersRowWithTextNote(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $paymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba s textovou poznámkou',
            'amount' => 500,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '900002',
            'constant_symbol' => null,
            'note' => 'Textová poznámka v gridu',
            'state' => 'preparing',
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-grid"] .datagrid', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Platba s textovou poznámkou', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-group-grid"]');
        $I->seeElement('[data-test="payment-group-grid"] [title="Textová poznámka v gridu"]');
        $I->seeElement('[data-test="payment-split-action-'.$paymentId.'"]');
        $I->seeElement('[data-test="payment-email-action-'.$paymentId.'"]');

        $I->resizeWindow(900, 900);

        $compactLayout = $I->executeJS(<<<'JS'
const action = document.querySelector('[data-test^="payment-split-action-"]');
const dataRow = action?.closest('tr');
const actionRow = dataRow?.nextElementSibling;
const primaryActions = dataRow?.querySelector('.datagrid-actions-cell');
const visibleAction = actionRow?.querySelector('.btn');

return {
    actionRowDisplay: actionRow === null ? null : getComputedStyle(actionRow).display,
    primaryActionsDisplay: primaryActions === null ? null : getComputedStyle(primaryActions).display,
    buttonDecoration: visibleAction === null ? null : getComputedStyle(visibleAction).textDecorationLine,
};
JS);

        Assert::assertSame('table-row', $compactLayout['actionRowDisplay']);
        Assert::assertSame('none', $compactLayout['primaryActionsDisplay']);
        Assert::assertSame('none', $compactLayout['buttonDecoration']);

        foreach ([360, 393] as $width) {
            $I->resizeWindow($width, 900);

            $mobileLayout = $I->executeJS(<<<'JS'
const action = document.querySelector('[data-test^="payment-split-action-"]');
const dataRow = action?.closest('tr');
const actionRow = dataRow?.nextElementSibling;
const scroller = actionRow?.closest('.table-responsive');
const scrollerRect = scroller?.getBoundingClientRect();
const buttons = Array.from(actionRow?.querySelectorAll('a.btn, button.btn, input.btn') ?? []);

return {
    actionRowDisplay: actionRow === null ? null : getComputedStyle(actionRow).display,
    horizontalOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    scrollerScrollLeft: scroller?.scrollLeft ?? null,
    inaccessibleButtons: buttons.filter(button => {
        const rect = button.getBoundingClientRect();

        return scrollerRect === undefined || rect.left < scrollerRect.left - 1 || rect.right > scrollerRect.right + 1;
    }).length,
    rightInset: scrollerRect === undefined || buttons.length === 0
        ? null
        : Math.min(...buttons.map(button => scrollerRect.right - button.getBoundingClientRect().right)),
    smallButtons: buttons.filter(button => {
        const rect = button.getBoundingClientRect();

        return rect.width < 44 || rect.height < 44;
    }).length,
};
JS);

            Assert::assertSame('table-row', $mobileLayout['actionRowDisplay']);
            Assert::assertSame(0, $mobileLayout['horizontalOverflow']);
            Assert::assertSame(0, $mobileLayout['scrollerScrollLeft']);
            Assert::assertSame(0, $mobileLayout['inaccessibleButtons']);
            Assert::assertGreaterThanOrEqual(6, $mobileLayout['rightInset']);
            Assert::assertSame(0, $mobileLayout['smallButtons']);
        }
    }

    /** @group payment */
    public function splitPaymentIntoMultiplePayments(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $sourcePaymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Dělená účastnická platba',
            'person_id' => 987,
            'amount' => 1000,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '100100',
            'constant_symbol' => 308,
            'note' => 'Platba účastníka',
            'state' => 'preparing',
        ]);
        $I->haveInDatabase('pa_payment_email_recipients', [
            'payment_id' => $sourcePaymentId,
            'email_address' => 'participant@example.com',
        ]);
        $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Jiná platba ve skupině',
            'amount' => 100,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '100103',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-split-action-'.$sourcePaymentId.'"]');
        $I->waitForElementVisible('[data-test="payment-split-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="payment-split-infobar"]');
        $I->seeNumberOfElements('[data-test="payment-split-row"]', 1);

        $I->clickStable('.modal-footer [data-test="payment-split-add"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->waitForJS('return document.querySelectorAll(\'[data-test="payment-split-row"]\').length === 2;', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100101', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-amount', '300', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-note', 'Faktura zaměstnavatele', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-variableSymbol', '100101', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-amount', '200', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $I->waitForElementVisible('[data-test="payment-split-errors"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Každá nová platba musí mít jiný variabilní symbol.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-split-errors"]');
        $I->seeInDatabase('pa_payment', ['id' => $sourcePaymentId, 'amount' => 1000]);
        $I->dontSeeInDatabase('pa_payment', ['split_from_payment_id' => $sourcePaymentId]);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100103', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-variableSymbol', '100102', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $I->waitForElementVisible('[data-test="payment-split-errors"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Variabilní symbol 100103 je už použitý v této platební skupině.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-split-errors"]');
        $I->seeInDatabase('pa_payment', ['id' => $sourcePaymentId, 'amount' => 1000]);
        $I->dontSeeInDatabase('pa_payment', ['split_from_payment_id' => $sourcePaymentId]);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100101', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $I->waitForText('Platba byla rozdělena na více plateb.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementNotVisible('.modal-backdrop', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('pa_payment', [
            'id' => $sourcePaymentId,
            'amount' => 500,
        ]);

        foreach ([['100101', 300, 'Faktura zaměstnavatele'], ['100102', 200, 'Platba účastníka']] as [$variableSymbol, $amount, $note]) {
            $I->seeInDatabase('pa_payment', [
                'group_id' => $groupId,
                'name' => 'Dělená účastnická platba',
                'person_id' => 987,
                'amount' => $amount,
                'variable_symbol' => $variableSymbol,
                'constant_symbol' => 308,
                'note' => $note,
                'state' => 'preparing',
                'split_from_payment_id' => $sourcePaymentId,
            ]);
        }

        $splitPaymentIds = $I->grabColumnFromDatabase('pa_payment', 'id', [
            'split_from_payment_id' => $sourcePaymentId,
        ]);
        Assert::assertCount(2, $splitPaymentIds);
        foreach ($splitPaymentIds as $splitPaymentId) {
            $I->seeInDatabase('pa_payment_email_recipients', [
                'payment_id' => $splitPaymentId,
                'email_address' => 'participant@example.com',
            ]);
        }

        $I->seeNumberOfElements('//*[contains(text(), "Rozděleno z platby #'.$sourcePaymentId.'")]', 2);
    }
}
