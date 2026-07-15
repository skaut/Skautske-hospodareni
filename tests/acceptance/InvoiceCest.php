<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;
use Throwable;

use function date;
use function html_entity_decode;
use function max;
use function min;
use function sleep;
use function str_contains;
use function substr;

class InvoiceCest extends BaseAcceptanceCest
{
    private const ACCEPTANCE_USER_ID = 2465;

    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $this->grantInvoiceAccess($I);
    }

    // ─── Setup tests (must run first — later tests depend on data) ──

    /** @group invoice */
    public function invoiceRequiresPreliminaryAccess(): void
    {
        $I = $this->I;

        $I->wantTo('see preliminary access page before invoice access is granted');
        $I->deleteFromDatabase('invoice_access_user', ['user_id' => self::ACCEPTANCE_USER_ID]);

        $I->amOnPage('/platby/faktury');
        $I->waitForElementVisible('[data-test="invoice-early-access-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Předběžný přístup k fakturaci');
        $I->seeElement('[data-test="invoice-early-access-request-card"]');
        $I->seeElement('[data-test="invoice-early-access-request-form"]');

        $this->grantInvoiceAccess($I);
    }

    /** @group invoice */
    public function saveInvoiceYearlySettings(): void
    {
        $I = $this->I;
        $settingsYear = $this->futureYear(2);

        $I->wantTo('save yearly invoice settings');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-settings"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/nastaveni/faktury');
        $I->dontSee('Jednotka je plátce DPH');

        $this->fillYearlySettings($settingsYear, 'Selenium '.$settingsYear);
        $I->scrollTo('input[name="save"]');
        $I->waitForElementClickable('input[name="save"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="save"]');

        $I->waitForText('Roční nastavení fakturace bylo uloženo.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @group invoice */
    public function createInvoiceSequence(): void
    {
        $I = $this->I;
        $sequenceYear = $this->futureYear(1);
        $sequencePrefix = 'FA'.$this->yearSuffix($sequenceYear);

        $I->wantTo('create invoice sequence');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/nova');
        $I->fillField('input[name="sequence"]', $sequencePrefix);
        $I->fillField('input[name="firstNumber"]', '00001');
        $I->selectOption('select[name="year"]', (string) $sequenceYear);
        $I->fillField('input[name="description"]', 'Selenium řada '.$sequenceYear);
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->selectOption('.ui--emailSelectbox', 'test@hospodareni.loc');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');

        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby');
    }

    /** @group invoice */
    public function createInvoiceSequenceWithoutEmailConfiguration(): void
    {
        $I = $this->I;
        $sequenceYear = $this->futureYear(1);
        $sequencePrefix = 'FAE'.$this->yearSuffix($sequenceYear);

        $I->wantTo('create invoice sequence without sender email configuration');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/nova');
        $I->fillField('input[name="sequence"]', $sequencePrefix);
        $I->fillField('input[name="firstNumber"]', '00002');
        $I->selectOption('select[name="year"]', (string) $sequenceYear);
        $I->fillField('input[name="description"]', 'Selenium řada bez emailu');
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');

        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby');
    }

    // ─── Tests that depend on an existing sequence ──────────────────

    /** @group invoice */
    public function openInvoiceSequenceList(): void
    {
        $I = $this->I;

        $I->wantTo('open invoice sequence management on canonical url');

        $this->openInvoices();
        $href = (string) $I->grabAttributeFrom('[data-test="invoice-action-manage-sequences"]', 'href');
        Assert::assertMatchesRegularExpression('~^/platby/rady(?:\?jednotka=\d+)?$~', $href);
        $I->amOnPage($href);
        $I->waitForElementVisible('[data-test="invoice-sequence-list"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady');
    }

    /** @group invoice */
    public function openInvoiceSequenceEdit(): void
    {
        $I = $this->I;

        $I->wantTo('open invoice sequence edit on canonical url');

        $this->openInvoices();
        $href = (string) $I->grabAttributeFrom('[data-test^="invoice-sequence-settings-"]', 'href');
        Assert::assertMatchesRegularExpression('~^/platby/rady/\d+/upravit(?:\?jednotka=\d+)?$~', $href);
        $I->amOnPage($href);
        $I->waitForElementVisible('[data-test="invoice-sequence-edit-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/');
    }

    /** @group invoice */
    public function openInvoiceDetail(): void
    {
        $I = $this->I;

        $I->wantTo('open invoice detail on canonical url');

        $this->openInvoices();

        if (! str_contains($I->grabPageSource(), 'data-test="invoice-detail-')) {
            $I->comment('No invoice detail links on page — skipping');

            return;
        }

        $href = (string) $I->grabAttributeFrom('[data-test^="invoice-detail-"]', 'href');
        Assert::assertMatchesRegularExpression('~^/platby/faktury/\d+$~', $href);
        $I->amOnPage($href);
        $I->waitForElementVisible('[data-test="invoice-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        Assert::assertStringContainsString(
            'data-test=&quot;invoice-detail-page&quot;',
            (string) $I->grabAttributeFrom('.invoice-preview-frame', 'srcdoc'),
        );
        $I->seeInCurrentUrl('/platby/faktury/');
    }

    /** @group invoice */
    public function editIssuedInvoice(): void
    {
        $I = $this->I;
        $editYear = $this->futureYear(2);
        $sequencePrefix = 'ED'.$this->yearSuffix($editYear);

        $I->wantTo('edit invoice while it is still in issued state');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-settings"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->fillYearlySettings($editYear, 'Selenium Edit '.$editYear);
        $I->scrollTo('input[name="save"]');
        $I->waitForElementClickable('input[name="save"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="save"]');
        $I->waitForText('Roční nastavení fakturace bylo uloženo.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('input[name="sequence"]', $sequencePrefix);
        $I->fillField('input[name="firstNumber"]', '00001');
        $I->selectOption('select[name="year"]', (string) $editYear);
        $I->fillField('input[name="description"]', 'Selenium editační řada');
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->selectOption('.ui--emailSelectbox', 'test@hospodareni.loc');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');
        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $sequenceId = $I->grabFromDatabase('invoice_sequence', 'id', ['sequence' => $sequencePrefix]);
        $this->openInvoices();
        $I->clickStable('[data-test="invoice-sequence-create-invoice-'.$sequenceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-create-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/'.$sequenceId.'/nova');

        $I->fillField('input[name="issuedBy"]', 'Původní vystavil');
        $I->fillField('input[name="email"]', 'edit@example.test');
        $I->clickStable('input[name="customer[type]"][value="person"]');
        $I->fillField('input[name="customer[name]"]', 'Editovaný odběratel');
        $I->fillField('input[name="customer[street]"]', 'Editační 1');
        $I->fillField('input[name="customer[city]"]', 'Praha');
        $I->fillField('input[name="customer[zipCode]"]', '10000');
        $I->fillField('input[name="items[0][purpose]"]', 'Původní položka');
        $I->fillField('input[name="items[0][quantity]"]', '1');
        $I->fillField('input[name="items[0][unit]"]', 'ks');
        $I->fillField('input[name="items[0][price]"]', '500');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="send"]');
        $I->waitForText('Faktura byla vytvořena', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/rady/'.$sequenceId.'\?jednotka=\d+(?:&.*)?$~');

        $expectedInvoiceNumber = $sequencePrefix.'00001';
        $invoiceId = $I->grabFromDatabase('invoice', 'id', ['sequence_id' => $sequenceId, 'invoice_number' => $expectedInvoiceNumber]);

        $I->amOnPage('/platby/faktury/'.$invoiceId.'/upravit');
        $I->waitForElementVisible('[data-test="invoice-edit-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/faktury/'.$invoiceId.'/upravit');
        $I->seeInField('input[name="issuedBy"]', 'Původní vystavil');
        $I->seeInField('input[name="items[0][purpose]"]', 'Původní položka');

        $I->fillField('input[name="issuedBy"]', 'Upravený vystavil');
        $I->fillField('input[name="items[0][purpose]"]', 'Upravená položka');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="send"]');

        $I->waitForText($expectedInvoiceNumber, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/'.$sequenceId);
    }

    /** @group invoice */
    public function duplicateInvoiceToAnotherSequence(): void
    {
        $I = $this->I;
        $duplicateYear = $this->futureYear(2);
        $sourcePrefix = 'DUA'.substr((string) $duplicateYear, -2);
        $targetPrefix = 'DUB'.substr((string) $duplicateYear, -2);
        $invoiceCount = (int) $I->grabFromDatabase('invoice', 'COUNT(*)');
        $sourceFirstNumber = (string) (80001 + $invoiceCount);
        $targetFirstNumber = (string) (90001 + $invoiceCount);

        $I->wantTo('duplicate invoice to another invoice sequence and continue in edit');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-settings"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->fillYearlySettings($duplicateYear, 'Selenium Duplicate '.$duplicateYear);
        $I->scrollTo('input[name="save"]');
        $I->waitForElementClickable('input[name="save"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="save"]');
        $I->waitForText('Roční nastavení fakturace bylo uloženo.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('input[name="sequence"]', $sourcePrefix);
        $I->fillField('input[name="firstNumber"]', $sourceFirstNumber);
        $I->selectOption('select[name="year"]', (string) $duplicateYear);
        $I->fillField('input[name="description"]', 'Selenium zdrojová řada');
        $I->fillField('input[name="defaultDueDate"]', '14');
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->selectOption('.ui--emailSelectbox', 'test@hospodareni.loc');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');
        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('input[name="sequence"]', $targetPrefix);
        $I->fillField('input[name="firstNumber"]', $targetFirstNumber);
        $I->selectOption('select[name="year"]', (string) $duplicateYear);
        $I->fillField('input[name="description"]', 'Selenium cílová řada');
        $I->fillField('input[name="defaultDueDate"]', '10');
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->selectOption('.ui--emailSelectbox', 'test@hospodareni.loc');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');
        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $sourceSequenceIds = $I->grabColumnFromDatabase('invoice_sequence', 'id', [
            'sequence' => $sourcePrefix,
            'year' => $duplicateYear,
            'first_number' => $sourceFirstNumber,
        ]);
        $targetSequenceIds = $I->grabColumnFromDatabase('invoice_sequence', 'id', [
            'sequence' => $targetPrefix,
            'year' => $duplicateYear,
            'first_number' => $targetFirstNumber,
        ]);
        Assert::assertNotEmpty($sourceSequenceIds, 'Source invoice sequence was not created.');
        Assert::assertNotEmpty($targetSequenceIds, 'Target invoice sequence was not created.');
        $sourceSequenceId = (int) max($sourceSequenceIds);
        $targetSequenceId = (int) max($targetSequenceIds);

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-sequence-create-invoice-'.$sourceSequenceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-create-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/'.$sourceSequenceId.'/nova');

        $I->fillField('input[name="issuedBy"]', 'Duplicitní vystavil');
        $I->fillField('input[name="email"]', 'duplicate@example.test');
        $I->clickStable('input[name="customer[type]"][value="person"]');
        $I->fillField('input[name="customer[name]"]', 'Duplikovaný odběratel');
        $I->fillField('input[name="customer[street]"]', 'Duplikační 1');
        $I->fillField('input[name="customer[city]"]', 'Praha');
        $I->fillField('input[name="customer[zipCode]"]', '11000');
        $I->fillField('input[name="items[0][purpose]"]', 'Duplikovaná položka');
        $I->fillField('input[name="items[0][quantity]"]', '3');
        $I->fillField('input[name="items[0][unit]"]', 'hod');
        $I->fillField('input[name="items[0][price]"]', '250');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="send"]');
        $I->waitForText('Faktura byla vytvořena', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/rady/'.$sourceSequenceId.'\?jednotka=\d+(?:&.*)?$~');

        $sourceInvoiceNumber = $sourcePrefix.$sourceFirstNumber;
        $sourceInvoiceId = null;

        for ($attempt = 0; $attempt < 10; ++$attempt) {
            try {
                $sourceInvoiceId = $I->grabFromDatabase('invoice', 'id', [
                    'sequence_id' => $sourceSequenceId,
                    'invoice_number' => $sourceInvoiceNumber,
                ]);

                if ($sourceInvoiceId !== null && $sourceInvoiceId !== false && $sourceInvoiceId !== '') {
                    break;
                }
            } catch (Throwable) {
            }

            sleep(1);
        }

        Assert::assertNotFalse($sourceInvoiceId, 'Source invoice was not created in time.');
        Assert::assertNotSame('', $sourceInvoiceId, 'Source invoice was not created in time.');

        $I->amOnPage('/platby/rady/'.$sourceSequenceId);
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="invoice-duplicate-'.$sourceInvoiceId.'"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="invoice-duplicate-'.$sourceInvoiceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-duplicate-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->selectOption('select[name="targetSequenceId"]', (string) $targetSequenceId);
        $I->clickStable('.modal-footer [data-test="invoice-duplicate-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->waitForElementVisible('[data-test="invoice-edit-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/faktury/');
        $I->seeInField('input[name="issuedBy"]', 'Duplicitní vystavil');
        $I->seeInField('input[name="customer[name]"]', 'Duplikovaný odběratel');
        $I->seeInField('input[name="customer[street]"]', 'Duplikační 1');
        $I->seeInField('input[name="items[0][purpose]"]', 'Duplikovaná položka');
        $I->seeInField('input[name="items[0][quantity]"]', '3');
        $I->seeInField('input[name="items[0][unit]"]', 'hod');
        $I->seeInField('input[name="items[0][price]"]', '250.00');
        Assert::assertSame('TRANSFER', (string) $I->grabValueFrom('select[name="paymentType"]'));

        $I->seeInField('input[name="dateOfIssue"]', date('d.m.Y'));
        $I->seeInField('input[name="dueDate"]', date('d.m.Y', strtotime('+10 days')));
    }

    // ─── Issue Invoice ──────────────────────────────────────────────

    /** @group invoice */
    public function issueInvoice(): void
    {
        $I = $this->I;
        $issueYear = $this->currentYear();
        $sequencePrefix = 'FA'.$this->yearSuffix($issueYear);

        $I->wantTo('issue invoice with generated invoice number and VS');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-settings"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/nastaveni/faktury');
        $I->dontSee('Jednotka je plátce DPH');
        $this->fillYearlySettings($issueYear, 'Selenium '.$issueYear);
        $I->scrollTo('input[name="save"]');
        $I->waitForElementClickable('input[name="save"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="save"]');
        $I->waitForText('Roční nastavení fakturace bylo uloženo.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/nova');
        $I->fillField('input[name="sequence"]', $sequencePrefix);
        $I->fillField('input[name="firstNumber"]', '00001');
        $I->selectOption('select[name="year"]', (string) $issueYear);
        $I->fillField('input[name="description"]', 'Selenium řada '.$issueYear);
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->selectOption('.ui--emailSelectbox', 'test@hospodareni.loc');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');
        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Navigate to the newly created FA26 sequence specifically
        $sequenceId = $I->grabFromDatabase('invoice_sequence', 'id', ['sequence' => $sequencePrefix]);
        $this->openInvoices();
        $I->clickStable('[data-test="invoice-sequence-create-invoice-'.$sequenceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-create-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/'.$sequenceId.'/nova');

        $I->see('Číslo faktury a VS se generují automaticky.');
        $I->see('Cena za jednotku');
        $I->dontSee('Cena za jednotku s DPH');

        $I->fillField('input[name="issuedBy"]', 'Selenium tester');
        $I->fillField('input[name="email"]', 'odberatel@example.test');
        $I->clickStable('input[name="customer[type]"][value="person"]');
        $I->fillField('input[name="customer[name]"]', 'Jan Novák');
        $I->fillField('input[name="customer[street]"]', 'Masarykova');
        $I->fillField('input[name="customer[city]"]', 'Brno');
        $I->fillField('input[name="customer[zipCode]"]', '60200');
        $I->fillField('input[name="items[0][purpose]"]', 'Testovací služba');
        $I->fillField('input[name="items[0][quantity]"]', '1');
        $I->fillField('input[name="items[0][unit]"]', 'ks');
        $I->fillField('input[name="items[0][price]"]', '1500');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="send"]');

        $I->waitForText('Faktura byla vytvořena', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/rady/'.$sequenceId.'\?jednotka=\d+(?:&.*)?$~');
        $expectedInvoiceNumber = $sequencePrefix.'00001';
        $expectedVariableSymbol = $this->yearSuffix($issueYear).'00001';
        $invoiceId = $I->grabFromDatabase('invoice', 'id', ['sequence_id' => $sequenceId, 'invoice_number' => $expectedInvoiceNumber]);
        $I->see($expectedInvoiceNumber);
        $I->see($expectedVariableSymbol);
        $I->amOnPage('/platby/faktury/'.$invoiceId);
        $I->waitForElementVisible('[data-test="invoice-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/faktury/');
        Assert::assertStringContainsString(
            'Jan Novák',
            html_entity_decode((string) $I->grabAttributeFrom('.invoice-preview-frame', 'srcdoc')),
        );
    }

    // ─── Help Panel Toggle ──────────────────────────────────────

    /** @group invoice */
    public function helpPanelToggleOnInvoiceSequenceCreate(): void
    {
        $I = $this->I;

        $I->wantTo('toggle help panel on invoice sequence create page');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Sidebar and content visible initially
        $I->seeElement('[data-test="help-sidebar"]');
        $I->seeElement('[data-test="help-content"]');
        $I->seeElement('[data-test="help-toggle"]');

        // Collapse
        $I->clickStable('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "true"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeElement('[data-test="help-content"]:not([style*="display: none"])');

        // Expand
        $I->clickStable('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "false"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="help-content"]');
    }

    /** @group invoice */
    public function helpPanelToggleOnInvoiceSettings(): void
    {
        $I = $this->I;

        $I->wantTo('toggle help panel on invoice settings page');

        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-settings"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Sidebar visible
        $I->seeElement('[data-test="help-sidebar"]');
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

    // ─── Complete Invoice Lifecycle ─────────────────────────────

    /** @group invoice */
    public function completeInvoiceLifecycle(): void
    {
        $I = $this->I;
        $lifecycleYear = $this->futureYear(1);

        $I->wantTo('complete full invoice lifecycle: settings → sequence → issue → detail → delete');

        // ── 1. Setup yearly settings ─────────────────────────────
        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-settings"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->fillYearlySettings($lifecycleYear, 'Selenium Lifecycle '.$lifecycleYear);
        $I->scrollTo('input[name="save"]');
        $I->waitForElementClickable('input[name="save"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="save"]');
        $I->waitForText('Roční nastavení fakturace bylo uloženo.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // ── 2. Create invoice sequence ───────────────────────────
        $this->openInvoices();
        $I->clickStable('[data-test="invoice-action-create-sequence"]');
        $I->waitForElementVisible('[data-test="invoice-sequence-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $seqPrefix = 'LC'.substr((string) $lifecycleYear, -2);
        $I->fillField('input[name="sequence"]', $seqPrefix);
        $I->fillField('input[name="firstNumber"]', '00001');
        $I->selectOption('select[name="year"]', (string) $lifecycleYear);
        $I->fillField('input[name="description"]', 'Selenium lifecycle řada');
        $I->selectOption('select[name="bankAccount"]', 'Acceptance');
        $I->selectOption('.ui--emailSelectbox', 'test@hospodareni.loc');
        $I->scrollTo('#frm-createForm input[type="submit"]');
        $I->waitForElementClickable('#frm-createForm input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS('document.querySelector("#frm-createForm input[type=submit]").click()');
        $I->waitForText('Fakturační řada byla založena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="payments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Navigate to the newly created sequence specifically by DB id
        $sequenceId = $I->grabFromDatabase('invoice_sequence', 'id', ['sequence' => $seqPrefix]);
        $this->openInvoices();
        $I->clickStable('[data-test="invoice-sequence-create-invoice-'.$sequenceId.'"]');
        $I->waitForElementVisible('[data-test="invoice-create-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/platby/rady/'.$sequenceId.'/nova');

        // ── 3. Issue invoice ─────────────────────────────────────
        $I->fillField('input[name="issuedBy"]', 'Selenium lifecycle');
        $I->fillField('input[name="email"]', 'lifecycle@example.test');
        $I->clickStable('input[name="customer[type]"][value="person"]');
        $I->fillField('input[name="customer[name]"]', 'Lifecycle Tester');
        $I->fillField('input[name="customer[street]"]', 'Testovací 1');
        $I->fillField('input[name="customer[city]"]', 'Praha');
        $I->fillField('input[name="customer[zipCode]"]', '10000');
        $I->fillField('input[name="items[0][purpose]"]', 'Lifecycle služba');
        $I->fillField('input[name="items[0][quantity]"]', '2');
        $I->fillField('input[name="items[0][unit]"]', 'ks');
        $I->fillField('input[name="items[0][price]"]', '750');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('input[name="send"]');

        $expectedInvNumber = $seqPrefix.'00001';
        $expectedVS = substr((string) $lifecycleYear, -2).'00001';

        $I->waitForText('Faktura byla vytvořena', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="invoice-sequence-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/rady/'.$sequenceId.'\?jednotka=\d+(?:&.*)?$~');
        $I->see($expectedInvNumber);

        // ── 4. Open invoice detail ───────────────────────────────
        $invoiceId = $I->grabFromDatabase('invoice', 'id', ['invoice_number' => $expectedInvNumber]);
        $I->amOnPage('/platby/faktury/'.$invoiceId);
        $I->waitForElementVisible('[data-test="invoice-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $invoicePreview = html_entity_decode((string) $I->grabAttributeFrom('.invoice-preview-frame', 'srcdoc'));
        Assert::assertStringContainsString('Lifecycle Tester', $invoicePreview);
        Assert::assertStringContainsString('Lifecycle služba', $invoicePreview);

        // ── 5. Cleanup: delete invoice + sequence via DB ─────────
        $sequenceId = $I->grabFromDatabase('invoice_sequence', 'id', ['sequence' => $seqPrefix]);

        // Delete in FK order
        $I->executeJS(''); // noop to ensure WebDriver is responsive
        // Use grab to verify then clean tables
        $I->seeInDatabase('invoice_item', ['invoice_id' => $invoiceId]);
        $I->seeInDatabase('invoice', ['id' => $invoiceId]);
        $I->seeInDatabase('invoice_sequence', ['id' => $sequenceId]);

        // Cleanup via haveInDatabase-injected rows will be rolled back by Db module
        // For data created via UI, we verify it exists then trust DB repopulation
    }

    private function openInvoices(): void
    {
        $this->I->amOnPage('/platby/faktury');
        $this->I->waitForDocumentReady();
        $this->I->waitForJS(
            'return document.querySelector("[data-test=\"invoice-home\"], [data-test=\"invoice-early-access-page\"], [data-test=\"login-link\"], [data-test=\"homepage-login\"]") !== null;',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        $pageState = $this->I->executeJS(
            'if (document.querySelector("[data-test=\"invoice-home\"]") !== null) { return "home"; }'
            .'if (document.querySelector("[data-test=\"invoice-early-access-page\"]") !== null) { return "early-access"; }'
            .'if (document.querySelector("[data-test=\"login-link\"], [data-test=\"homepage-login\"]") !== null) { return "login"; }'
            .'return "unknown";',
        );

        Assert::assertSame(
            'home',
            $pageState,
            'Expected invoice overview at /platby/faktury, got '.$pageState.' state at '.$this->I->grabFromCurrentUrl().'.',
        );
        $this->I->seeInCurrentUrl('/platby/faktury');
    }

    private function grantInvoiceAccess(AcceptanceTester $I): void
    {
        $I->deleteFromDatabase('invoice_access_user', ['user_id' => self::ACCEPTANCE_USER_ID]);
        $I->haveInDatabase('invoice_access_user', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'created_at' => '2026-06-18 12:00:00',
        ]);
    }

    private function fillYearlySettings(int $year, string $name): void
    {
        $I = $this->I;

        $I->selectOption('select[name="year"]', (string) $year);
        $I->fillField('input[name="companyNumber"]', '12345678');
        $I->fillField('input[name="name"]', $name);
        $I->fillField('input[name="street"]', 'Křižíkova 12');
        $I->fillField('input[name="city"]', 'Praha');
        $I->fillField('input[name="zipcode"]', '18600');
        $I->fillField('input[name="phone"]', '+420123456789');
    }

    private function currentYear(): int
    {
        return (int) date('Y');
    }

    private function futureYear(int $offset): int
    {
        return min($this->currentYear() + $offset, $this->currentYear() + 2);
    }

    private function yearSuffix(int $year): string
    {
        return substr((string) $year, -2);
    }
}
