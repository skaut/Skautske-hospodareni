<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use Page\Payment;
use PHPUnit\Framework\Assert;

use function str_contains;
use function uniqid;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentCest extends BaseAcceptanceCest
{
    private const ACCEPTANCE_USER_ID = 2465;

    protected AcceptanceTester $I;
    protected Payment $page;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $this->page = new Payment($I);
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group payment */
    public function createPaymentGroup(): void
    {
        $I = $this->I;
        $page = $this->page;

        $I->wantTo('create payment group');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná');
        $I->seeInCurrentUrl('/platby/skupiny/nova');
        $I->fillField('Název', 'Jaráky');
        $I->click('//option[text()="Vyberte e-mail"]');
        $I->click('//option[text()="test@hospodareni.loc"]');

        $I->click('//option[text()="Vyberte bankovní účet"]');
        $I->click('//option[text()="Acceptance"]');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]');
        $I->executeJS('document.querySelector(\'input[name="send"]\').click()');

        $I->waitForText('Skupina byla založena', 15);

        $I->wantTo('create payments');

        $I->amGoingTo('add first payment');
        $page->addPayment('Testovací platba 1', null, 500);

        $I->amGoingTo('add second payment');
        $page->addPayment('Testovací platba 2', null, 500);

        $I->amGoingTo('add third payment');
        $page->addPayment('Testovací platba 3', 'frantisekmasa1@gmail.com', 300);

        $I->wantTo('complete payment');

        $I->amGoingTo('mark second payment as complete');
        $I->waitForJS(
            'return document.querySelectorAll(\'a[title="Zaplaceno"]\').length >= 2;',
            10,
        );
        $I->executeJS(
            'var links = Array.from(document.querySelectorAll(\'a[title="Zaplaceno"]\'))'
            .'.filter(function (link) { return link.offsetParent !== null; });'
            .'if (links.length < 2) { return false; }'
            .'links[1].click();'
            .'return true;',
        );
        $I->waitForText('Dokončena', 10);

        $I->canSeeNumberOfElements('(//*[text()="Nezaplacena"])', 2);
        $I->see('Dokončena');

        $I->wantTo('send payment email');

        $I->amGoingTo('send third payment');
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'a[title="Odeslat e-mail o platbě"].ui--sendEmail, a[title="Odeslat upomínku"].ui--sendEmail\'))'
            .'.some(function (link) { return link.offsetParent !== null; });',
            10,
        );
        $I->executeJS(
            'var links = Array.from(document.querySelectorAll(\'a[title="Odeslat e-mail o platbě"].ui--sendEmail, a[title="Odeslat upomínku"].ui--sendEmail\'))'
            .'.filter(function (link) { return link.offsetParent !== null; });'
            .'if (links.length === 0) { return false; }'
            .'links[links.length - 1].click();'
            .'return true;',
        );
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(".alert"))'
            .'.some(function (alert) { return alert.textContent.toLowerCase().includes("e-mail"); });',
            10,
        );

        $page->seeNumberOfPaymentsWithState('Nezaplacena', 2);
        $I->see('1 / 3 plateb'); // Progress bar: 1 paid out of 3 total

        $I->wantTo('close and reopen group');
        $I->click('Uzavřít');
        $I->waitForText('Znovu otevřít');
        $I->click('Znovu otevřít');
        $I->waitForText('Uzavřít');

        $I->amGoingTo('close group');
        $I->click('Uzavřít');
    }

    /** @group payment */
    public function registrationCreateLinkUsesCanonicalUrl(): void
    {
        $I = $this->I;

        $I->wantTo('see canonical registration create link in payment group list');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForElementVisible('[data-test="payments-groups-page"]', 10);
        $I->seeInCurrentUrl('/platby/skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);

        Assert::assertSame(
            '/platby/registrace/nova',
            $I->grabAttributeFrom('[data-test="create-button-item-registration"]', 'href'),
        );
    }

    /** @group payment */
    public function paymentGroupListUsesDataGridWithRowActions(): void
    {
        $I = $this->I;
        $bankAccountId = $I->haveInDatabase('pa_bank_account', [
            'unit_id' => AcceptanceTester::UNIT_ID,
            'name' => 'Párovatelný účet pro DataGrid',
            'token' => 'acceptance-fio-token',
            'transaction_source' => 'fio',
            'created_at' => '2026-06-18 12:00:00',
            'allowed_for_subunits' => 1,
            'number_prefix' => null,
            'number_number' => '2000942145',
            'number_bank_code' => '2010',
        ]);
        $groupId = $this->createSubtypePaymentGroup('event', $bankAccountId);
        $olderGroupId = $this->createSubtypePaymentGroup('event', $bankAccountId);
        $I->updateInDatabase('pa_group', ['created_at' => '2026-06-18 12:00:00'], ['id' => $groupId]);
        $I->updateInDatabase('pa_group', ['created_at' => '2025-01-02 12:00:00'], ['id' => $olderGroupId]);

        $I->amOnPage('/platby/skupiny');
        $I->waitForElementVisible('[data-test="payments-groups-grid"] .datagrid', 10);
        $I->see('Vytvořeno', '[data-test="payments-groups-grid"] thead');
        $I->see('18. 6. 2026', '[data-test="payments-groups-grid"]');
        Assert::assertTrue($I->executeJS(
            'const newer = document.querySelector(\'[data-test="payment-group-name-'.$groupId.'"]\');'
            .'const older = document.querySelector(\'[data-test="payment-group-name-'.$olderGroupId.'"]\');'
            .'return Boolean(newer.compareDocumentPosition(older) & Node.DOCUMENT_POSITION_FOLLOWING);',
        ));

        $I->click('#datagrid-sort-createdAt');
        $I->waitForJS(
            'const newer = document.querySelector(\'[data-test="payment-group-name-'.$groupId.'"]\');'
            .'const older = document.querySelector(\'[data-test="payment-group-name-'.$olderGroupId.'"]\');'
            .'return Boolean(older.compareDocumentPosition(newer) & Node.DOCUMENT_POSITION_FOLLOWING);',
            10,
        );

        $detailSelector = '[data-test="payment-group-detail-'.$groupId.'"]';
        $settingsSelector = '[data-test="payment-group-settings-'.$groupId.'"]';
        $cloneSelector = '[data-test="payment-group-clone-'.$groupId.'"]';
        $pairSelector = '[data-test="payment-group-pair-'.$groupId.'"]';

        $I->seeElement($detailSelector);
        $I->seeElement($settingsSelector);
        $I->seeElement($cloneSelector);
        $I->seeElement($pairSelector);
        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/platby',
            $I->grabAttributeFrom($detailSelector, 'href'),
        );
        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/upravit',
            $I->grabAttributeFrom($settingsSelector, 'href'),
        );
        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/klonovat',
            $I->grabAttributeFrom($cloneSelector, 'href'),
        );
        Assert::assertNotSame('', $I->grabAttributeFrom($pairSelector, 'href'));

        $I->click($detailSelector);
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
    }

    /** @group payment */
    public function clonePaymentGroupCopiesOnlyValidatedSettings(): void
    {
        $I = $this->I;
        $sourceGroupId = $this->createSubtypePaymentGroup('event');
        $sourceName = 'Zdroj klonování '.uniqid('', true);
        $cloneName = 'Kopie skupiny '.uniqid('', true);
        $dueDate = ChronosDate::today()->addWeekdays(2);

        $I->updateInDatabase('pa_group', [
            'name' => $sourceName,
            'amount' => 1250.5,
            'due_date' => $dueDate->format('Y-m-d'),
            'constant_symbol' => 308,
            'next_variable_symbol' => '777',
            'automatic_pairing_enabled' => 1,
            'pairing_days_back' => 18,
            'is_reminders_enabled' => 1,
        ], ['id' => $sourceGroupId]);
        $I->updateInDatabase('pa_group_email', [
            'template_subject' => 'Klonovaný předmět',
            'template_body' => 'Klonované tělo',
        ], [
            'group_id' => $sourceGroupId,
            'type' => 'payment_info',
        ]);
        $I->haveInDatabase('pa_payment', [
            'group_id' => $sourceGroupId,
            'name' => 'Data zdrojové skupiny',
            'amount' => 100,
            'due_date' => ChronosDate::today()->format('Y-m-d'),
            'variable_symbol' => '991001',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);

        $groupsBefore = $I->grabNumRecords('pa_group');
        $I->amOnPage('/platby/skupiny');
        $I->waitForElementVisible('[data-test="payment-group-clone-'.$sourceGroupId.'"]', 10);
        $I->click('[data-test="payment-group-clone-'.$sourceGroupId.'"]');
        $I->waitForElementVisible('[data-test="payment-group-clone-page"]', 10);
        $I->seeInCurrentUrl('/platby/skupiny/'.$sourceGroupId.'/klonovat');

        Assert::assertSame($groupsBefore, $I->grabNumRecords('pa_group'));
        Assert::assertSame($sourceName, $I->grabValueFrom('input[name="name"]'));
        Assert::assertSame('1250.5', $I->grabValueFrom('input[name="amount"]'));
        Assert::assertSame($dueDate->format('d.m.Y'), $I->grabValueFrom('input[name="dueDate"]'));
        Assert::assertSame('308', $I->grabValueFrom('input[name="constantSymbol"]'));
        Assert::assertNotSame('777', $I->grabValueFrom('input[name="nextVs"]'));
        $I->seeInField('input[name="emails[payment_info][subject]"]', 'Klonovaný předmět');
        $I->seeInField('textarea[name="emails[payment_info][body]"]', 'Klonované tělo');

        $I->fillFieldStable('input[name="name"]', '');
        $I->clickStable('input[name="send"]');
        $I->waitForText('Musíte zadat název skupiny', 10);
        Assert::assertSame($groupsBefore, $I->grabNumRecords('pa_group'));
        $I->seeInCurrentUrl('/platby/skupiny/'.$sourceGroupId.'/klonovat');

        $I->fillFieldStable('input[name="name"]', $cloneName);
        $I->clickStable('input[name="send"]');
        $I->waitForText('Skupina byla založena', 15);
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);

        Assert::assertSame($groupsBefore + 1, $I->grabNumRecords('pa_group'));
        $cloneId = $I->grabFromDatabase('pa_group', 'id', ['name' => $cloneName]);
        $I->seeInDatabase('pa_group', [
            'id' => $cloneId,
            'amount' => 1250.5,
            'due_date' => $dueDate->format('Y-m-d'),
            'constant_symbol' => 308,
            'bank_account_id' => 1,
            'automatic_pairing_enabled' => 1,
            'pairing_days_back' => 18,
            'is_reminders_enabled' => 1,
            'groupType' => null,
            'sisId' => null,
        ]);
        Assert::assertNotSame(
            '777',
            (string) $I->grabFromDatabase('pa_group', 'next_variable_symbol', ['id' => $cloneId]),
        );
        $I->seeInDatabase('pa_group_email', [
            'group_id' => $cloneId,
            'type' => 'payment_info',
            'template_subject' => 'Klonovaný předmět',
            'template_body' => 'Klonované tělo',
        ]);
        $I->dontSeeInDatabase('pa_payment', ['group_id' => $cloneId]);
        $I->seeInDatabase('pa_payment', [
            'group_id' => $sourceGroupId,
            'name' => 'Data zdrojové skupiny',
        ]);
    }

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

        $I->amOnPage('/platby');
        $I->waitForElementVisible('[data-test="dashboard-group-card-'.$groupId.'"]', 10);

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
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
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
        $I->waitForElementVisible($dashboardCard, 10);
        $I->seeElement($dashboardCard.'.navigation-card');
        $I->seeElement('[data-test="dashboard-sequence-link-'.$sequenceId.'"].stretched-link');

        $I->click('[data-test="dashboard-sequence-settings-'.$sequenceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-edit-page"]', 10);

        $I->amOnPage('/platby');
        $I->waitForElementVisible('[data-test="payments-link-invoices"]', 10);
        $I->click('[data-test="payments-link-invoices"]');
        $I->waitForElementVisible('[data-test="invoice-home"]', 10);

        $invoiceCard = '[data-test="invoice-sequence-card-'.$sequenceId.'"]';
        $I->seeElement($invoiceCard.'.navigation-card');
        $I->seeElement('[data-test="invoice-sequence-link-'.$sequenceId.'"].stretched-link');
        $I->executeJS(
            'const card = document.querySelector(\''.$invoiceCard.'\');'
            .'const rect = card.getBoundingClientRect();'
            .'document.elementFromPoint(rect.right - 20, rect.top + 20).click();',
        );
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', 10);
    }

    /** @group payment */
    public function subtypeCreateLinksUseCanonicalUrls(): void
    {
        $I = $this->I;

        $I->wantTo('open payment subtype selectors on canonical urls');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);

        Assert::assertSame('/platby/tabory', $I->grabAttributeFrom('[data-test="create-button-item-camp"]', 'href'));
        Assert::assertSame('/platby/akce', $I->grabAttributeFrom('[data-test="create-button-item-event"]', 'href'));
        Assert::assertSame('/platby/vzdelavacky', $I->grabAttributeFrom('[data-test="create-button-item-education"]', 'href'));

        $I->clickStable('[data-test="create-button-item-camp"]');
        $I->waitForText('Nová táborová skupina plateb', 10);
        $I->seeInCurrentUrl('/platby/tabory');

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);
        $I->clickStable('[data-test="create-button-item-event"]');
        $I->waitForText('Nová skupina plateb pro akci', 10);
        $I->seeInCurrentUrl('/platby/akce');

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);
        $I->clickStable('[data-test="create-button-item-education"]');
        $I->waitForText('Nová skupina plateb vzdělávací akce', 10);
        $I->seeInCurrentUrl('/platby/vzdelavacky');
    }

    /** @group payment */
    public function subtypeCreateFormsUseCanonicalUrls(): void
    {
        $I = $this->I;

        $I->wantTo('open payment subtype create forms on canonical urls when selectable records exist');

        $this->openPaymentSubtypeSelector('[data-test="create-button-item-camp"]', 'Nová táborová skupina plateb', '/platby/tabory');
        if (str_contains($I->grabPageSource(), 'data-test="payment-camp-create-')) {
            Assert::assertMatchesRegularExpression(
                '~^/platby/tabory/\d+/nova(?:\?.*)?$~',
                (string) $I->grabAttributeFrom('[data-test^="payment-camp-create-"]', 'href'),
            );
            $I->click('[data-test^="payment-camp-create-"]');
            $I->waitForElementVisible('[data-test="payment-camp-create-group-page"]', 10);
            $I->seeCurrentUrlMatches('~^/platby/tabory/\d+/nova(?:\?.*)?$~');
        }

        $this->openPaymentSubtypeSelector('[data-test="create-button-item-event"]', 'Nová skupina plateb pro akci', '/platby/akce');
        if (str_contains($I->grabPageSource(), 'data-test="payment-event-create-')) {
            $eventHref = (string) $I->grabAttributeFrom('[data-test^="payment-event-create-"]', 'href');

            if ($eventHref !== '#' && $eventHref !== '') {
                Assert::assertMatchesRegularExpression(
                    '~^/platby/akce/\d+/nova(?:\?.*)?$~',
                    $eventHref,
                );
                $I->clickStable('[data-test^="payment-event-create-"]');
                $I->waitForElementVisible('[data-test="payment-event-create-group-page"]', 10);
                $I->seeCurrentUrlMatches('~^/platby/akce/\d+/nova(?:\?.*)?$~');
            } else {
                $I->comment('Event create link has no valid href — skipping');
            }
        } else {
            $I->comment('No events available for payment group creation — skipping event subtype check');
        }

        $this->openPaymentSubtypeSelector('[data-test="create-button-item-education"]', 'Nová skupina plateb vzdělávací akce', '/platby/vzdelavacky');
        if (str_contains($I->grabPageSource(), 'data-test="payment-education-create-')) {
            Assert::assertMatchesRegularExpression(
                '~^/platby/vzdelavacky/\d+/nova(?:\?.*)?$~',
                (string) $I->grabAttributeFrom('[data-test^="payment-education-create-"]', 'href'),
            );
            $I->click('[data-test^="payment-education-create-"]');
            $I->waitForElementVisible('[data-test="payment-education-create-group-page"]', 10);
            $I->seeCurrentUrlMatches('~^/platby/vzdelavacky/\d+/nova(?:\?.*)?$~');
        }
    }

    /** @group payment */
    public function openPaymentGroupOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium URL ', true);

        $I->wantTo('open a payment group on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);

        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->executeJS(
            "document.evaluate(\"//a[normalize-space(text())='$groupName']\", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();",
        );
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
        $I->waitForText($groupName);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
    }

    /** @group payment */
    public function openPaymentGroupEditOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Edit URL ', true);

        $I->wantTo('open payment group edit on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->click('[data-test="payment-group-edit-link"]');
        $I->waitForElementVisible('[data-test="payment-group-form-page"]', 10);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/upravit(?:\?.*)?$~');
        $I->waitForText('Editace skupiny: '.$groupName);
    }

    /** @group payment */
    public function openPaymentRepaymentsOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Repayment URL ', true);

        $I->wantTo('open payment repayments on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->click('[data-test="payment-group-repayments-link"]');
        $I->waitForElementVisible('[data-test="payment-repayments-page"]', 10);
        $I->waitForText('Vratky');
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
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);
        $I->clickStable('[data-test="payment-add-button-item-member"]');
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', 10);
        $I->waitForText('Přidat osoby z jednotky');
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/osoby(?:\?.*)?$~');
    }

    /** @group payment */
    public function massAddSelectsEmailsByContactType(): void
    {
        $I = $this->I;

        $this->createGeneralPaymentGroup(uniqid('Selenium MassAdd emails ', true));
        $I->seeElement('[data-test="payment-group-detail-page"] .page-heading .badge.text-bg-success');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);
        $I->clickStable('[data-test="payment-add-button-item-member"]');
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', 10);

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
        $I->click($toggle);
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\'))'
            .'.every(option => option.selected);',
            10,
        );

        $I->click($toggle);
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\'))'
            .'.every(option => !option.selected);',
            10,
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
    public function participantAddLinkUsesCanonicalUrlForSubtypeGroups(): void
    {
        $I = $this->I;

        $I->wantTo('see canonical participant add link for camp, event and education payment groups');

        foreach (['camp', 'event', 'education'] as $type) {
            $groupId = $this->createSubtypePaymentGroup($type);

            $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
            $I->waitForText('Přidat platbu');
            $I->clickStable('[data-test="payment-add-button-toggle"]');
            $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);

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
        $I->waitForText('Přidat platbu');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);

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
        $I->waitForText('Přidat platbu');

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
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);

        $selector = '[data-test="payment-email-action-'.$paymentId.'"]';
        $I->seeElement($selector.'.btn-light.disabled');
        $I->seeElement($selector.'[title="Skupina nemá nastavený e-mail odesílatele"]');
        $I->dontSeeElement($selector.'.ui--sendEmail');
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
        $I->waitForElementVisible('[data-test="payment-group-grid"] .datagrid', 10);
        $I->waitForText('Platba s textovou poznámkou', 10, '[data-test="payment-group-grid"]');
        $I->seeElement('[data-test="payment-group-grid"] [title="Textová poznámka v gridu"]');
        $I->seeElement('[data-test="payment-split-action-'.$paymentId.'"]');
        $I->seeElement('[data-test="payment-email-action-'.$paymentId.'"]');
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
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
        $I->clickStable('[data-test="payment-split-action-'.$sourcePaymentId.'"]');
        $I->waitForElementVisible('[data-test="payment-split-form"]', 10);
        $I->seeElement('[data-test="payment-split-infobar"]');
        $I->seeNumberOfElements('[data-test="payment-split-row"]', 1);

        $I->clickStable('.modal-footer [data-test="payment-split-add"]', 10, false);
        $I->waitForJS('return document.querySelectorAll(\'[data-test="payment-split-row"]\').length === 2;', 10);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100101', 10, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-amount', '300', 10, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-note', 'Faktura zaměstnavatele', 10, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-variableSymbol', '100101', 10, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-amount', '200', 10, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', 10, false);

        $I->waitForText('Každá nová platba musí mít jiný variabilní symbol.', 10);
        $I->seeInDatabase('pa_payment', ['id' => $sourcePaymentId, 'amount' => 1000]);
        $I->dontSeeInDatabase('pa_payment', ['split_from_payment_id' => $sourcePaymentId]);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100103', 10, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-variableSymbol', '100102', 10, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', 10, false);

        $I->waitForText('Variabilní symbol 100103 je už použitý v této platební skupině.', 10);
        $I->seeInDatabase('pa_payment', ['id' => $sourcePaymentId, 'amount' => 1000]);
        $I->dontSeeInDatabase('pa_payment', ['split_from_payment_id' => $sourcePaymentId]);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100101', 10, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', 10, false);

        $I->waitForText('Platba byla rozdělena na více plateb.', 10);
        $I->waitForElementNotVisible('.modal-backdrop', 10);
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

    private function openPaymentSubtypeSelector(string $menuItemSelector, string $pageTitle, string $currentUrl): void
    {
        $I = $this->I;

        $I->clickStable('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);
        $I->clickStable($menuItemSelector);
        $I->waitForText($pageTitle, 10);
        $I->seeInCurrentUrl($currentUrl);
    }

    private function createGeneralPaymentGroup(string $groupName): void
    {
        $I = $this->I;

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná');
        $I->seeInCurrentUrl('/platby/skupiny/nova');
        $I->fillField('Název', $groupName);
        $I->click('//option[text()="Vyberte e-mail"]');
        $I->click('//option[text()="test@hospodareni.loc"]');
        $I->click('//option[text()="Vyberte bankovní účet"]');
        $I->click('//option[text()="Acceptance"]');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]');
        $I->executeJS('document.querySelector(\'input[name="send"]\').click()');
        $I->waitForText('Skupina byla založena', 15);
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
    }

    private function createSubtypePaymentGroup(string $type, int $bankAccountId = 1): int
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

    /** @group settings */
    public function openSystemSettingsFromPaymentUtilityNavigation(): void
    {
        $I = $this->I;

        $I->wantTo('open system settings from the utility navigation on the payment dashboard');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->seeInCurrentUrl('/platby');
        $I->seeElement('[data-test="payment-nav-overview"]');
        $I->seeElement('[data-test="payments-card-groups"].navigation-card');
        $I->seeElement('[data-test="payments-link-groups"].stretched-link');
        Assert::assertSame('/platby/skupiny', $I->grabAttributeFrom('[data-test="payments-link-groups"]', 'href'));
        $I->seeElement('[data-test="payments-card-invoices"].navigation-card');
        $I->seeElement('[data-test="payments-link-invoices"].stretched-link');
        $I->dontSeeElement('[data-test="payments-card-settings"]');
        $I->waitForElementVisible('[data-test="utility-nav-settings"]', 10);
        $I->click('[data-test="utility-nav-settings"]');

        $I->waitForText('Nastavení', 10);
        $I->seeInCurrentUrl('/nastaveni');
    }

    // ─── Help Panel Toggle ──────────────────────────────────────

    /** @group payment */
    public function helpPanelToggleOnGroupCreate(): void
    {
        $I = $this->I;

        $I->wantTo('toggle help panel on payment group create page');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná');

        // Sidebar and content visible initially
        $I->seeElement('[data-test="help-sidebar"]');
        $I->seeElement('[data-test="help-content"]');
        $I->seeElement('[data-test="help-toggle"]');

        // Collapse
        $I->click('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "true"', 5);

        $collapsed = $I->executeJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed');
        Assert::assertSame('true', $collapsed);

        // Expand
        $I->click('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "false"', 5);

        $expanded = $I->executeJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed');
        Assert::assertSame('false', $expanded);
    }
}
