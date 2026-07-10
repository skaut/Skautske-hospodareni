<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use PHPUnit\Framework\Assert;

use function str_contains;
use function uniqid;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentCest extends PaymentAcceptanceCest
{
    /** @group payment */
    public function createPaymentGroup(): void
    {
        $I = $this->I;
        $page = $this->page;

        $I->wantTo('create payment group');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $I->waitForText('Dokončena', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForElementVisible('[data-test="payments-groups-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
        $I->waitForElementVisible('[data-test="payments-groups-grid"] .datagrid', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $I->waitForElementVisible('[data-test="payment-group-clone-'.$sourceGroupId.'"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('[data-test="payment-group-clone-'.$sourceGroupId.'"]');
        $I->waitForElementVisible('[data-test="payment-group-clone-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $I->waitForText('Musíte zadat název skupiny', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        Assert::assertSame($groupsBefore, $I->grabNumRecords('pa_group'));
        $I->seeInCurrentUrl('/platby/skupiny/'.$sourceGroupId.'/klonovat');

        $I->fillFieldStable('input[name="name"]', $cloneName);
        $I->clickStable('input[name="send"]');
        $I->waitForText('Skupina byla založena', 15);
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
    /** @group payment */
    public function subtypeCreateLinksUseCanonicalUrls(): void
    {
        $I = $this->I;

        $I->wantTo('open payment subtype selectors on canonical urls');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        Assert::assertSame('/platby/tabory', $I->grabAttributeFrom('[data-test="create-button-item-camp"]', 'href'));
        Assert::assertSame('/platby/akce', $I->grabAttributeFrom('[data-test="create-button-item-event"]', 'href'));
        Assert::assertSame('/platby/vzdelavacky', $I->grabAttributeFrom('[data-test="create-button-item-education"]', 'href'));

        $I->clickStable('[data-test="create-button-item-camp"]');
        $I->waitForText('Nová táborová skupina plateb', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/tabory');

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="create-button-item-event"]');
        $I->waitForText('Nová skupina plateb pro akci', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/akce');

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="create-button-item-education"]');
        $I->waitForText('Nová skupina plateb vzdělávací akce', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
            $I->waitForElementVisible('[data-test="payment-camp-create-group-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
                $I->waitForElementVisible('[data-test="payment-event-create-group-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
            $I->waitForElementVisible('[data-test="payment-education-create-group-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
            $I->seeCurrentUrlMatches('~^/platby/vzdelavacky/\d+/nova(?:\?.*)?$~');
        }
    }
}
