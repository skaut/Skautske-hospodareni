<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;

class SettingsCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    // ─── Overview Page ───────────────────────────────────────────

    /** @group settings */
    public function settingsOverviewDisplaysCardsAndLinks(): void
    {
        $I = $this->I;

        $I->wantTo('verify settings overview page shows cards with correct links');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        // Main nav active state
        $I->seeElement('.active [data-test="global-nav-settings"]');

        // Submenu — Přehled active
        $I->seeElement('[data-test="settings-subnav-overview"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-bank-accounts"].btn-light');
        $I->seeElement('[data-test="settings-subnav-mails"].btn-light');
        $I->seeElement('[data-test="settings-subnav-invoices"].btn-light');
        $I->seeElement('[data-test="settings-subnav-automation"].btn-light');

        // Cards present
        $I->seeElement('[data-test="settings-card-bank-accounts"]');
        $I->seeElement('[data-test="settings-card-mails"]');
        $I->seeElement('[data-test="settings-card-invoices"]');
        $I->seeElement('[data-test="settings-card-automation"]');

        // Card links present
        $I->seeElement('[data-test="settings-link-bank-accounts"]');
        $I->seeElement('[data-test="settings-link-mails"]');
        $I->seeElement('[data-test="settings-link-invoices"]');
        $I->seeElement('[data-test="settings-link-automation"]');
    }

    // ─── Submenu Navigation ──────────────────────────────────────

    /** @group settings */
    public function settingsSubmenuHighlightsActiveSection(): void
    {
        $I = $this->I;

        $I->wantTo('verify settings submenu highlights the correct active section');

        // Overview active
        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);
        $I->seeElement('[data-test="settings-subnav-overview"].btn-primary');

        // Bank accounts active
        $I->click('[data-test="settings-subnav-bank-accounts"]');
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);
        $I->seeElement('[data-test="settings-subnav-bank-accounts"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-overview"].btn-light');

        // Mails active
        $I->click('[data-test="settings-subnav-mails"]');
        $I->waitForElementVisible('[data-test="settings-mails-page"]', 10);
        $I->seeElement('[data-test="settings-subnav-mails"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-bank-accounts"].btn-light');

        // Invoices active
        $I->click('[data-test="settings-subnav-invoices"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', 10);
        $I->seeElement('[data-test="settings-subnav-invoices"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-mails"].btn-light');

        // Automation active
        $I->click('[data-test="settings-subnav-automation"]');
        $I->waitForElementVisible('[data-test="settings-automation-page"]', 10);
        $I->seeElement('[data-test="settings-subnav-automation"].btn-primary');
        $I->seeElement('[data-test="settings-subnav-invoices"].btn-light');
    }

    /** @group settings */
    public function settingsSubmenuLinksNavigateCorrectly(): void
    {
        $I = $this->I;

        $I->wantTo('verify settings submenu pill buttons navigate to correct pages');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-subnav-bank-accounts"]');
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/bankovni-ucty');

        $I->click('[data-test="settings-subnav-mails"]');
        $I->waitForElementVisible('[data-test="settings-mails-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/emaily');

        $I->click('[data-test="settings-subnav-invoices"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/faktury');

        $I->click('[data-test="settings-subnav-automation"]');
        $I->waitForElementVisible('[data-test="settings-automation-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/automatizace');

        $I->click('[data-test="settings-subnav-overview"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni');
    }

    // ─── Card Link Navigation from Overview ──────────────────────

    /** @group settings */
    public function settingsOverviewCardLinksNavigateCorrectly(): void
    {
        $I = $this->I;

        $I->wantTo('verify clicking cards on settings overview navigates to the correct section');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-link-bank-accounts"]');
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/bankovni-ucty');

        $I->click('[data-test="settings-subnav-overview"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-link-mails"]');
        $I->waitForElementVisible('[data-test="settings-mails-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/emaily');

        $I->click('[data-test="settings-subnav-overview"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-link-invoices"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/faktury');

        $I->click('[data-test="settings-subnav-overview"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-link-automation"]');
        $I->waitForElementVisible('[data-test="settings-automation-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/automatizace');
    }

    // ─── Bank Accounts — Layout & Empty State ────────────────────

    /** @group settings */
    public function settingsBankAccountsPageDisplaysCorrectLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify bank accounts page displays hero, add/import buttons, and account list');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-subnav-bank-accounts"]');
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);

        // Action buttons visible
        $I->seeElement('[data-test="settings-bank-accounts-add"]');
        $I->seeElement('[data-test="settings-bank-accounts-import"]');
    }

    // ─── CRUD: Create, Read, Edit, Delete Bank Account ───────────

    /** @group settings */
    public function settingsBankAccountCrudWorkflow(): void
    {
        $I = $this->I;
        $I->disablePopups();

        $I->wantTo('create, read, edit, and delete a bank account');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-subnav-bank-accounts"]');
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);

        // ── CREATE ───────────────────────────────────────────────
        $I->click('[data-test="settings-bank-accounts-add"]');
        $I->waitForElementVisible('[data-test="settings-bank-account-new-page"]', 10);

        // Fill form
        $I->fillField('input[name="name"]', 'Testovací účet Selenium');
        $I->fillField('input[name="number"]', '2000942144');
        $I->selectOption('select[name="bankCode"]', '0100');
        $I->selectOption('select[name="transactionSource"]', 'gpc');
        $I->scrollTo('input[type="submit"]');
        $I->waitForElementClickable('input[type="submit"]');
        $I->click('input[type="submit"]');

        // Wait for PRG redirect to complete — flash message is the reliable indicator
        $I->waitForText('Bankovní účet byl uložen', 15);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);

        // ── READ ─────────────────────────────────────────────────
        // Verify the new account appears in DB
        $I->seeInDatabase('pa_bank_account', ['name' => 'Testovací účet Selenium']);
        $accountId = $I->grabFromDatabase('pa_bank_account', 'id', ['name' => 'Testovací účet Selenium']);

        // Verify in the list
        $I->seeElement('[data-test="settings-bank-accounts-list"]');

        // ── DETAIL ───────────────────────────────────────────────
        // Navigate to detail page
        $I->amOnPage('/nastaveni/bankovni-ucty/'.$accountId);
        $I->waitForElementVisible('[data-test="settings-bank-account-detail-page"]', 10);
        $I->see('Testovací účet Selenium');
        $I->seeElement('[data-test="settings-bank-account-open-settings"]');

        // ── EDIT ─────────────────────────────────────────────────
        $I->click('[data-test="settings-bank-account-open-settings"]');
        $I->waitForElementVisible('[data-test="settings-bank-account-edit-page"]', 10);

        $I->fillField('input[name="name"]', 'Upravený účet Selenium');
        $I->scrollTo('input[type="submit"]');
        $I->waitForElementClickable('input[type="submit"]');
        $I->click('input[type="submit"]');
        $I->waitForText('Bankovní účet byl uložen', 15);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);

        // Verify update in DB
        $I->seeInDatabase('pa_bank_account', ['id' => $accountId, 'name' => 'Upravený účet Selenium']);
        $I->dontSeeInDatabase('pa_bank_account', ['name' => 'Testovací účet Selenium']);

        // ── DELETE ───────────────────────────────────────────────
        $I->amOnPage('/nastaveni/bankovni-ucty/'.$accountId.'/upravit');
        $I->waitForElementVisible('[data-test="settings-bank-account-edit-page"]', 10);

        $I->disablePopups();
        $I->click('[data-test="settings-bank-account-remove"]');
        $I->waitForText('odstraněn', 15);
        $I->waitForElementVisible('[data-test="settings-bank-accounts-page"]', 10);

        // Verify deletion
        $I->dontSeeInDatabase('pa_bank_account', ['id' => $accountId]);
    }

    // ─── Mails Page — Layout ─────────────────────────────────────

    /** @group settings */
    public function settingsMailsPageDisplaysLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify mails settings page displays hero and connect button');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-subnav-mails"]');
        $I->waitForElementVisible('[data-test="settings-mails-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/emaily');
        $I->seeElement('[data-test="settings-mail-connect"]');
    }

    // ─── Invoices Page — Layout ──────────────────────────────────

    /** @group settings */
    public function settingsInvoicesPageDisplaysLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify invoice settings page displays hero and back link');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-subnav-invoices"]');
        $I->waitForElementVisible('[data-test="invoice-settings-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/faktury');
        $I->seeElement('[data-test="invoice-settings-back"]');
    }

    // ─── Automation Page — Layout ────────────────────────────────

    /** @group settings */
    public function settingsAutomationPageDisplaysLayout(): void
    {
        $I = $this->I;

        $I->wantTo('verify automation page displays CRON and history cards');

        $I->click('[data-test="global-nav-settings"]');
        $I->waitForElementVisible('[data-test="settings-page"]', 10);

        $I->click('[data-test="settings-subnav-automation"]');
        $I->waitForElementVisible('[data-test="settings-automation-page"]', 10);
        $I->seeInCurrentUrl('/nastaveni/automatizace');
        $I->seeElement('[data-test="settings-automation-card-cron"]');
        $I->seeElement('[data-test="settings-automation-card-history"]');
    }
}
