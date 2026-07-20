<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

class SettingsCest extends BaseAcceptanceCest
{
    private const ACCEPTANCE_USER_ID = 2465;

    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $I->deleteFromDatabase('invoice_access_user', ['user_id' => self::ACCEPTANCE_USER_ID]);
        $I->haveInDatabase('invoice_access_user', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'created_at' => '2026-06-18 12:00:00',
        ]);
        $I->deleteFromDatabase('user_preference', ['user_id' => self::ACCEPTANCE_USER_ID]);
        $I->haveInDatabase('user_preference', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'show_help' => 1,
            'updated_at' => '2026-06-18 12:00:00',
        ]);
    }

    // ─── Overview Page ───────────────────────────────────────────

    /** @group settings */
    public function settingsOverviewDisplaysCardsAndLinks(): void
    {
        $I = $this->I;

        $I->wantTo('verify settings overview page shows cards with correct links');

        $this->openSettingsOverview();

        // Utility navigation active state
        $I->seeElement('.active [data-test="utility-nav-settings"]');

        // Submenu — Přehled active
        $I->seeElement('[data-test="settings-subnav-overview"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-user"].btn-light');
        $I->seeElement('[data-test="settings-subnav-bank-accounts"].btn-light');
        $I->seeElement('[data-test="settings-subnav-mails"].btn-light');
        $I->seeElement('[data-test="settings-subnav-invoices"].btn-light');
        $I->seeElement('[data-test="settings-subnav-automation"].btn-light');

        // Cards present
        $I->seeElement('[data-test="settings-card-bank-accounts"].navigation-card');
        $I->seeElement('[data-test="settings-card-mails"].navigation-card');
        $I->seeElement('[data-test="settings-card-invoices"].navigation-card');
        $I->seeElement('[data-test="settings-card-user"].navigation-card');
        $I->seeElement('[data-test="settings-card-automation"].navigation-card');

        // Card links present
        $I->seeElement('[data-test="settings-link-bank-accounts"].stretched-link');
        $I->seeElement('[data-test="settings-link-mails"].stretched-link');
        $I->seeElement('[data-test="settings-link-invoices"].stretched-link');
        $I->seeElement('[data-test="settings-link-user"].stretched-link');
        $I->seeElement('[data-test="settings-link-automation"].stretched-link');
    }

    // ─── Submenu Navigation ──────────────────────────────────────

    /** @group settings */
    public function settingsSubmenuHighlightsActiveSection(): void
    {
        $I = $this->I;

        $I->wantTo('verify settings submenu highlights the correct active section');

        // Overview active
        $this->openSettingsOverview();
        $I->seeElement('[data-test="settings-subnav-overview"].btn-primary');

        // User active
        $this->openSettingsSubpage('[data-test="settings-subnav-user"]', '[data-test="settings-user-page"]', '/nastaveni/uzivatel');
        $I->seeElement('[data-test="settings-subnav-user"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-overview"].btn-light');

        // Bank accounts active
        $this->openSettingsSubpage('[data-test="settings-subnav-bank-accounts"]', '[data-test="settings-bank-accounts-page"]', '/nastaveni/bankovni-ucty');
        $I->seeElement('[data-test="settings-subnav-bank-accounts"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-overview"].btn-light');

        // Mails active
        $this->openSettingsSubpage('[data-test="settings-subnav-mails"]', '[data-test="settings-mails-page"]', '/nastaveni/emaily');
        $I->seeElement('[data-test="settings-subnav-mails"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-bank-accounts"].btn-light');

        // Invoices active
        $this->openSettingsSubpage('[data-test="settings-subnav-invoices"]', '[data-test="invoice-settings-page"]', '/nastaveni/faktury');
        $I->seeElement('[data-test="settings-subnav-invoices"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-mails"].btn-light');

        // Automation active
        $this->openSettingsSubpage('[data-test="settings-subnav-automation"]', '[data-test="settings-automation-page"]', '/nastaveni/automatizace');
        $I->seeElement('[data-test="settings-subnav-automation"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-invoices"].btn-light');
    }

    /** @group settings */
    public function settingsSubmenuLinksNavigateCorrectly(): void
    {
        $I = $this->I;

        $I->wantTo('verify settings submenu pill buttons navigate to correct pages');

        $this->openSettingsOverview();

        $this->openSettingsSubpage('[data-test="settings-subnav-user"]', '[data-test="settings-user-page"]', '/nastaveni/uzivatel');
        $I->seeInCurrentUrl('/nastaveni/uzivatel');

        $this->openSettingsSubpage('[data-test="settings-subnav-bank-accounts"]', '[data-test="settings-bank-accounts-page"]', '/nastaveni/bankovni-ucty');
        $I->seeInCurrentUrl('/nastaveni/bankovni-ucty');

        $this->openSettingsSubpage('[data-test="settings-subnav-mails"]', '[data-test="settings-mails-page"]', '/nastaveni/emaily');
        $I->seeInCurrentUrl('/nastaveni/emaily');

        $this->openSettingsSubpage('[data-test="settings-subnav-invoices"]', '[data-test="invoice-settings-page"]', '/nastaveni/faktury');
        $I->seeInCurrentUrl('/nastaveni/faktury');

        $this->openSettingsSubpage('[data-test="settings-subnav-automation"]', '[data-test="settings-automation-page"]', '/nastaveni/automatizace');
        $I->seeInCurrentUrl('/nastaveni/automatizace');

        $this->openSettingsSubpage('[data-test="settings-subnav-overview"]', '[data-test="settings-page"]', '/nastaveni');
        $I->seeInCurrentUrl('/nastaveni');
    }

    // ─── Card Link Navigation from Overview ──────────────────────

    /** @group settings */
    public function settingsOverviewCardLinksNavigateCorrectly(): void
    {
        $I = $this->I;

        $I->wantTo('verify clicking cards on settings overview navigates to the correct section');

        $this->openSettingsOverview();

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-link-bank-accounts"]',
            '[data-test="settings-bank-accounts-page"]',
            '/nastaveni/bankovni-ucty',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-subnav-overview"]',
            '[data-test="settings-page"]',
            '/nastaveni',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-link-user"]',
            '[data-test="settings-user-page"]',
            '/nastaveni/uzivatel',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-subnav-overview"]',
            '[data-test="settings-page"]',
            '/nastaveni',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-link-mails"]',
            '[data-test="settings-mails-page"]',
            '/nastaveni/emaily',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-subnav-overview"]',
            '[data-test="settings-page"]',
            '/nastaveni',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-link-invoices"]',
            '[data-test="invoice-settings-page"]',
            '/nastaveni/faktury',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-subnav-overview"]',
            '[data-test="settings-page"]',
            '/nastaveni',
        );

        $this->openLinkAndWaitForElementWithSkautisRetry(
            $I,
            '[data-test="settings-link-automation"]',
            '[data-test="settings-automation-page"]',
            '/nastaveni/automatizace',
        );
    }

    /** @group settings */
    public function userCanDisableAutomaticHelpDisplay(): void
    {
        $I = $this->I;

        $I->wantTo('hide page help by default and open it with the title icon');

        $this->openUserSettingsPage();

        $I->seeElement('.page-heading .page-lead > [data-page-help-toggle]');
        $I->seeElement('[data-page-help-content]:not([hidden])');
        $I->uncheckOption('input[name="showHelp"]');
        $I->click('input[type="submit"]');
        $I->waitForElementVisible('[data-test="settings-user-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->seeInDatabase('user_preference', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'show_help' => 0,
        ]);
        $I->seeElement('[data-page-help-toggle][aria-expanded="false"]');
        $I->seeElement('.page-heading .page-lead[data-page-help-expanded="false"]');
        $I->dontSeeElement('[data-page-help-content]:not([hidden])');

        $I->click('[data-page-help-toggle]');
        $I->waitForJS('return document.querySelector(".page-heading")?.dataset.pageHelpExpanded === "true"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('.page-heading .page-lead[data-page-help-expanded="true"]');
        $I->seeElement('[data-page-help-content]:not([hidden])');

        $this->openSettingsSubpage('[data-test="settings-subnav-invoices"]', '[data-test="invoice-settings-page"]', '/nastaveni/faktury');
        $I->seeElement('[data-page-help-toggle][aria-expanded="false"]');
        $I->seeElement('[data-help-layout][data-help-collapsed="true"]');
        $I->seeElement('[data-help-toggle][aria-expanded="false"]');

        $I->click('[data-help-toggle]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "false"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-help-toggle][aria-expanded="true"]');
    }

    /** @group settings */
    public function userCanEnableBackgroundSkautisLoginExtension(): void
    {
        $I = $this->I;

        $I->wantTo('enable background SkautIS login extension in user settings');

        $this->openUserSettingsPage();

        $I->dontSeeElement('body[data-session-keep-alive-url]');
        $I->checkOption('input[name="extendSkautisLogin"]');
        $I->checkOption('input[name="rememberSkautisRole"]');
        $I->click('input[type="submit"]');
        $I->waitForElementVisible('[data-test="settings-user-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->seeInDatabase('user_preference', [
            'user_id' => self::ACCEPTANCE_USER_ID,
            'extend_skautis_login' => 1,
            'remember_skautis_role' => 1,
        ]);
        Assert::assertGreaterThan(
            0,
            (int) $I->grabFromDatabase('user_preference', 'remembered_skautis_role_id', [
                'user_id' => self::ACCEPTANCE_USER_ID,
            ]),
        );
        $I->seeElement('input[name="extendSkautisLogin"]:checked');
        $I->seeElement('input[name="rememberSkautisRole"]:checked');
        $I->seeElement('body[data-session-keep-alive-url][data-session-keep-alive-interval]');
    }

    // ─── Bank Accounts — Layout & Empty State ────────────────────

    /** @group settings */
    public function settingsBankAccountsPageDisplaysCorrectLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify bank accounts page displays hero, add/import buttons, and account list');

        $this->openBankAccountsSettingsPage();

        // Action buttons visible
        $I->seeElement('[data-test="settings-bank-accounts-add"]');
        $I->seeElement('[data-test="settings-bank-accounts-import"]');
        $I->seeElement('[data-test="settings-bank-accounts-list"] .datagrid');
    }

    // ─── CRUD: Create, Read, Edit, Delete Bank Account ───────────

    /** @group settings */
    public function settingsBankAccountCrudWorkflow(): void
    {
        $I = $this->I;
        $I->disablePopups();

        $I->wantTo('create, read, edit, and delete a bank account');

        $this->openBankAccountsSettingsPage();

        // ── CREATE ───────────────────────────────────────────────
        $I->clickStable('[data-test="settings-bank-accounts-add"]');
        $I->waitForElementVisible('[data-test="settings-bank-account-new-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $labelColorsMatchBodyInBothThemes = $I->executeJS(<<<'JS'
            const label = document.querySelector('[data-test="settings-bank-account-new-page"] form label');
            const originalTheme = document.documentElement.getAttribute('data-bs-theme');
            const matchesBody = () => getComputedStyle(label).color === getComputedStyle(document.body).color;
            document.documentElement.setAttribute('data-bs-theme', 'light');
            const lightMatches = matchesBody();
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            const darkMatches = matchesBody();
            if (originalTheme === null) {
                document.documentElement.removeAttribute('data-bs-theme');
            } else {
                document.documentElement.setAttribute('data-bs-theme', originalTheme);
            }
            return lightMatches
                && darkMatches
                && document.querySelector('[data-test="settings-bank-account-new-page"] form label.form-label') !== null;
            JS);
        Assert::assertTrue($labelColorsMatchBodyInBothThemes);

        // Fill form
        $I->fillField('input[name="name"]', 'Testovací účet Selenium');
        $I->fillField('input[name="number"]', '2000942144');
        $I->selectOption('select[name="bankCode"]', '0100');
        $I->selectOption('select[name="transactionSource"]', 'gpc');
        $I->scrollTo('input[type="submit"]');
        $I->waitForElementClickable('input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('input[type="submit"]');

        // Wait for PRG redirect to complete — flash message is the reliable indicator
        $I->waitForPageTextStable('Bankovní účet byl uložen', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // ── READ ─────────────────────────────────────────────────
        // Verify the new account appears in DB
        $I->seeInDatabase('pa_bank_account', ['name' => 'Testovací účet Selenium']);
        $accountId = $I->grabFromDatabase('pa_bank_account', 'id', ['name' => 'Testovací účet Selenium']);

        // Verify in the list
        $I->seeElement('[data-test="settings-bank-accounts-list"]');

        // ── DETAIL ───────────────────────────────────────────────
        // Navigate to detail page
        $I->amOnPage('/nastaveni/bankovni-ucty/'.$accountId);
        $I->waitForElementVisible('[data-test="settings-bank-account-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Testovací účet Selenium');
        $I->seeElement('[data-test="settings-bank-account-open-settings"]');

        // ── EDIT ─────────────────────────────────────────────────
        $I->clickStable('[data-test="settings-bank-account-open-settings"]');
        $I->waitForElementVisible('[data-test="settings-bank-account-edit-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillField('input[name="name"]', 'Upravený účet Selenium');
        $I->scrollTo('input[type="submit"]');
        $I->waitForElementClickable('input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('input[type="submit"]');
        $I->waitForPageTextStable('Bankovní účet byl uložen', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Verify update in DB
        $I->seeInDatabase('pa_bank_account', ['id' => $accountId, 'name' => 'Upravený účet Selenium']);
        $I->dontSeeInDatabase('pa_bank_account', ['name' => 'Testovací účet Selenium']);

        // ── DELETE ───────────────────────────────────────────────
        $I->amOnPage('/nastaveni/bankovni-ucty/'.$accountId.'/upravit');
        $I->waitForElementVisible('[data-test="settings-bank-account-edit-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->disablePopups();
        $I->clickStable('[data-test="settings-bank-account-remove"]');
        $I->waitForPageTextStable('odstraněn', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Verify deletion
        $I->dontSeeInDatabase('pa_bank_account', ['id' => $accountId]);
    }

    /** @group settings */
    public function settingsFioBankAccountCanUseFioApi(): void
    {
        $I = $this->I;
        $token = 'acceptance-fio-token';
        $accountName = 'Fio účet Selenium';

        $I->wantTo('create a Fio bank account with FIO API transaction source');

        $I->deleteFromDatabase('pa_bank_account', ['name' => $accountName]);
        $this->openNewBankAccountForm();

        $this->fillAndSubmitFioBankAccountForm($accountName, $token);

        $I->waitForPageTextStable('Bankovní účet byl uložen', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('pa_bank_account', [
            'name' => $accountName,
            'number_bank_code' => '2010',
            'transaction_source' => 'fio',
            'token' => $token,
        ]);
    }

    /** @group settings */
    public function settingsFioBankAccountRejectsInvalidFioApiToken(): void
    {
        $I = $this->I;
        $accountName = 'Fio účet Selenium neplatný token';

        $I->wantTo('reject invalid Fio API token when creating a bank account');

        $I->deleteFromDatabase('pa_bank_account', ['name' => $accountName]);
        $this->openNewBankAccountForm();

        $this->fillAndSubmitFioBankAccountForm($accountName, 'acceptance-invalid-fio-token');

        $I->waitForPageTextStable('Fio token není platný nebo ještě není aktivní.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="settings-bank-account-new-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeInDatabase('pa_bank_account', [
            'name' => $accountName,
        ]);
    }

    private function openSettingsOverview(): void
    {
        $this->openLinkAndWaitForElementWithSkautisRetry(
            $this->I,
            '[data-test="utility-nav-settings"]',
            '[data-test="settings-page"]',
            '/nastaveni',
        );
    }

    private function openSettingsSubpage(
        string $linkSelector,
        string $expectedSelector,
        string $expectedUrlPart,
    ): void {
        $this->openLinkAndWaitForElementWithSkautisRetry(
            $this->I,
            $linkSelector,
            $expectedSelector,
            $expectedUrlPart,
        );
    }

    private function openSettingsSubpageFromOverview(
        string $linkSelector,
        string $expectedSelector,
        string $expectedUrlPart,
    ): void {
        $this->openSettingsOverview();
        $this->openSettingsSubpage($linkSelector, $expectedSelector, $expectedUrlPart);
    }

    private function openUserSettingsPage(): void
    {
        $this->openSettingsSubpageFromOverview(
            '[data-test="settings-subnav-user"]',
            '[data-test="settings-user-page"]',
            '/nastaveni/uzivatel',
        );
    }

    private function openBankAccountsSettingsPage(): void
    {
        $this->openSettingsSubpageFromOverview(
            '[data-test="settings-subnav-bank-accounts"]',
            '[data-test="settings-bank-accounts-page"]',
            '/nastaveni/bankovni-ucty',
        );
    }

    private function openNewBankAccountForm(): void
    {
        $I = $this->I;

        $this->openBankAccountsSettingsPage();
        $I->clickStable('[data-test="settings-bank-accounts-add"]');
        $I->waitForElementVisible('[data-test="settings-bank-account-new-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    private function fillAndSubmitFioBankAccountForm(string $accountName, string $token): void
    {
        $I = $this->I;

        $I->fillField('input[name="name"]', $accountName);
        $I->fillField('input[name="number"]', '1231231230');
        $I->selectOption('select[name="bankCode"]', '2010');
        $I->selectOption('select[name="transactionSource"]', 'fio');
        $I->fillField('input[name="token"]', $token);
        $I->scrollTo('input[type="submit"]');
        $I->waitForElementClickable('input[type="submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('input[type="submit"]');
    }

    // ─── Mails Page — Layout ─────────────────────────────────────

    /** @group settings */
    public function settingsMailsPageDisplaysLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify mails settings page displays hero and connect button');

        $this->openSettingsSubpageFromOverview('[data-test="settings-subnav-mails"]', '[data-test="settings-mails-page"]', '/nastaveni/emaily');
        $I->seeInCurrentUrl('/nastaveni/emaily');
        $I->seeElement('[data-test="settings-mail-connect"]');
    }

    // ─── Invoices Page — Layout ──────────────────────────────────

    /** @group settings */
    public function settingsInvoicesPageDisplaysLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify invoice settings page displays hero and back link');

        $this->openSettingsSubpageFromOverview('[data-test="settings-subnav-invoices"]', '[data-test="invoice-settings-page"]', '/nastaveni/faktury');
        $I->seeInCurrentUrl('/nastaveni/faktury');
        $I->seeElement('[data-test="invoice-settings-back"]');
    }

    // ─── Automation Page — Layout ────────────────────────────────

    /** @group settings */
    public function settingsAutomationPageDisplaysLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify automation page displays CRON and history cards');

        $this->openSettingsSubpageFromOverview('[data-test="settings-subnav-automation"]', '[data-test="settings-automation-page"]', '/nastaveni/automatizace');
        $I->seeInCurrentUrl('/nastaveni/automatizace');
        $I->seeElement('[data-test="settings-automation-card-cron"]');
        $I->seeElement('[data-test="settings-automation-card-history"]');
    }
}
