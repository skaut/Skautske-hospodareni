<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

final class AdminCest extends BaseAcceptanceCest
{
    private const ACCEPTANCE_ADMIN_USER_ID = 2465;
    private const NEW_ADMIN_USER_ID = 9999;

    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    // ─── Permissions ─────────────────────────────────────────────

    /** @group admin */
    public function nonAdminCannotSeeOrOpenAdminSection(): void
    {
        $I = $this->I;

        $I->wantTo('verify that non-admin user cannot see or open admin section');

        $I->dontSeeElement('[data-test="utility-nav-admin"]');

        $I->amOnPage('/admin');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-page"]');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-users-page"]');

        $I->amOnPage('/admin/statistiky');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-statistics-page"]');

        $I->amOnPage('/admin/hlaseni-chyb');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-bug-reports-page"]');
    }

    /** @group admin */
    public function supportCanOnlyAccessBugReports(): void
    {
        $I = $this->I;
        $I->deleteFromDatabase('system_user_role', ['user_id' => self::ACCEPTANCE_ADMIN_USER_ID]);
        $I->haveInDatabase('system_user_role', [
            'user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
            'role' => 'support',
            'created_at' => '2026-09-07 12:00:00',
        ]);

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/hlaseni-chyb');
        $I->seeElement('[data-test="utility-nav-admin"]');
        $I->seeElement('[data-test="admin-nav-bug-reports"]');
        $I->dontSeeElement('[data-test="admin-nav-overview"]');
        $I->dontSeeElement('[data-test="admin-nav-users"]');
        $I->dontSeeElement('[data-test="admin-nav-statistics"]');
        $I->dontSeeElement('[data-test="admin-nav-invoice-access"]');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');

        $I->amOnPage('/admin/statistiky');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
    }

    // ─── Overview Page ───────────────────────────────────────────

    /** @group admin */
    public function adminOverviewDisplaysCardsAndLinksCorrectly(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin overview page shows cards with correct links');

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Utility navigation active state
        $I->seeElement('.active [data-test="utility-nav-admin"]');

        // Submenu pills — Přehled active
        $I->seeElement('[data-test="admin-nav-overview"].btn-primary');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Cards present
        $I->seeElement('[data-test="admin-card-users"].navigation-card');
        $I->seeElement('[data-test="admin-card-stats"].navigation-card');
        $I->seeElement('[data-test="admin-card-invoice-access"].navigation-card');
        $I->seeElement('[data-test="admin-card-bug-reports"].navigation-card');

        // Card links work
        $I->seeElement('[data-test="admin-link-users"].stretched-link');
        $usersHref = $I->grabAttributeFrom('[data-test="admin-link-users"]', 'href');
        Assert::assertStringContainsString('/admin/uzivatele', $usersHref);

        $I->seeElement('[data-test="admin-link-statistics"].stretched-link');
        $statsHref = $I->grabAttributeFrom('[data-test="admin-link-statistics"]', 'href');
        Assert::assertStringContainsString('/admin/statistiky', $statsHref);

        $I->seeElement('[data-test="admin-link-invoice-access"].stretched-link');
        $I->seeElement('[data-test="admin-link-bug-reports"].stretched-link');
    }

    // ─── Submenu Navigation ──────────────────────────────────────

    /** @group admin */
    public function adminSubmenuHighlightsActiveSection(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin submenu highlights the correct active section');

        // Overview active
        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-nav-overview"].btn-primary');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Users active
        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-nav-users"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Statistics active
        $I->amOnPage('/admin/statistiky');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-nav-statistics"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');

        // Bug reports active
        $I->amOnPage('/admin/hlaseni-chyb');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');
    }

    /** @group admin */
    public function adminSubmenuLinksNavigateCorrectly(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin submenu pill buttons navigate to correct pages');

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Click Users pill
        $I->clickStable('[data-test="admin-nav-users"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/uzivatele');

        // Click Statistics pill
        $I->clickStable('[data-test="admin-nav-statistics"]');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/statistiky');

        // Click Bug reports pill
        $I->clickStable('[data-test="admin-nav-bug-reports"]');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/hlaseni-chyb');

        // Click Overview pill (back)
        $I->clickStable('[data-test="admin-nav-overview"]');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin');
    }

    // ─── Users Page — Layout & Empty State ───────────────────────

    /** @group admin */
    public function adminUsersPageDisplaysCorrectLayout(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin users page displays hero, form toggle, and list card');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Hero card visible
        $I->seeElement('[data-test="admin-users-page"] .card');

        // Form toggle button visible
        $I->seeElement('[data-test="admin-users-form-toggle"]');

        // Form collapse is initially hidden
        $I->dontSeeElement('[data-test="admin-users-form-collapse"].show');

        // List card visible
        $I->seeElement('[data-test="admin-users-list-card"]');

        // Empty state visible when no users in DB (initial state after self insert)
        // We only have ourselves as admin user — first verify the list
        $I->seeElement('[data-test="admin-users-list"]');
    }

    // ─── CRUD: Create, Read, Update, Delete System User Roles ───

    /** @group admin */
    public function adminUserCrudWorkflow(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $I->disablePopups();

        $I->wantTo('create, read, update, and delete a user with system roles');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // ── CREATE ───────────────────────────────────────────────
        // Open form
        $I->clickStable('[data-test="admin-users-form-toggle"]');
        $I->waitForElementVisible('[data-test="admin-users-form"] input[name="userId"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Fill and submit
        $I->fillField('[data-test="admin-users-form"] input[name="userId"]', (string) self::NEW_ADMIN_USER_ID);
        $I->checkOption('input[name="roles[]"][value="support"]');
        $I->clickStable('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Verify flash message
        $I->seeElement('.alert-success');

        // ── READ ─────────────────────────────────────────────────
        // Verify user appears in the list
        $I->seeElement('[data-test="admin-users-list"]');
        $I->seeInDatabase('system_user_role', ['user_id' => self::NEW_ADMIN_USER_ID, 'role' => 'support']);
        $I->see('support', '[data-test="admin-user-roles-'.self::NEW_ADMIN_USER_ID.'"]');
        $I->seeElement('[data-test="admin-user-row-'.self::NEW_ADMIN_USER_ID.'"] td:nth-child(2)');

        // Verify at least 2 rows (self + new user)
        $I->seeNumberOfElements('[data-test="admin-users-list"] tbody tr', [2, 100]);

        // ── UPDATE ───────────────────────────────────────────────
        // Find the new user's row and click edit
        $newUser = self::NEW_ADMIN_USER_ID;
        $I->seeElement('[data-test="admin-user-edit-'.$newUser.'"]');
        $I->clickStable('[data-test="admin-user-edit-'.$newUser.'"]');

        // Form should be visible with edit mode
        $I->waitForElementVisible('[data-test="admin-users-form-collapse"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-users-form"]');
        $I->seeElement('[data-test="admin-users-form-cancel"]');
        $I->see((string) $newUser, '[data-test="admin-users-edited-user-id"]');
        $I->seeElement('h2.h4 [data-test="admin-users-edited-user-id"]');
        $I->dontSeeElement('[data-test="admin-users-form"] input[name="userId"]');
        $I->seeElement('[data-test="admin-users-roles-panel"]');
        $I->seeElement('[data-test="admin-users-roles-panel"] legend.fs-4');
        $I->seeNumberOfElements('[data-test="admin-users-role-options"] .form-check', 2);
        $I->seeElement('[data-test="admin-users-form-actions"] input[type="submit"]');
        $I->seeElement('[data-test="admin-users-form-actions"] [data-test="admin-users-form-cancel"]');
        $I->seeInCurrentUrl('edit='.$newUser);

        // Add admin role; both permissions must remain assigned.
        $I->checkOption('input[name="roles[]"][value="admin"]');
        $I->clickStable('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Verify update in DB
        $I->seeElement('.alert-success');
        $I->seeInDatabase('system_user_role', ['user_id' => self::NEW_ADMIN_USER_ID, 'role' => 'admin']);
        $I->seeInDatabase('system_user_role', ['user_id' => self::NEW_ADMIN_USER_ID, 'role' => 'support']);

        // ── DELETE ───────────────────────────────────────────────
        $I->waitForElementVisible('[data-test="admin-user-delete-'.$newUser.'"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->disablePopups();
        $I->clickStable('[data-test="admin-user-delete-'.$newUser.'"]');
        $I->waitForText('odebrán', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Verify deletion
        $I->seeElement('.alert-success');
        $I->dontSeeInDatabase('system_user_role', ['user_id' => self::NEW_ADMIN_USER_ID]);
    }

    /** @group admin */
    public function adminUserCreateRejectsDuplicateUserId(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify that creating a duplicate role assignment user is rejected');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Open form and try adding existing user_id
        $I->clickStable('[data-test="admin-users-form-toggle"]');
        $I->waitForElementVisible('[data-test="admin-users-form"] input[name="userId"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillFieldStable('[data-test="admin-users-form"] input[name="userId"]', (string) self::ACCEPTANCE_ADMIN_USER_ID);
        $I->checkOption('input[name="roles[]"][value="admin"]');
        $I->clickStable('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForJS('return document.querySelector(".alert-warning") !== null;', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Should show warning
        $I->seeElement('.alert-warning');
    }

    /** @group admin */
    public function adminUserEditCancelReturnsToDefaultView(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify cancelling edit returns to the default users view');

        $I->amOnPage('/admin/uzivatele?edit='.self::ACCEPTANCE_ADMIN_USER_ID);
        $I->waitForElementVisible('[data-test="admin-users-form-collapse"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-users-form-cancel"]');
        $I->see((string) self::ACCEPTANCE_ADMIN_USER_ID, '[data-test="admin-users-edited-user-id"]');
        $I->dontSeeElement('[data-test="admin-users-form"] input[name="userId"]');
        $I->seeElement('[data-test="admin-users-form-actions"] input[type="submit"]');
        $I->seeElement('[data-test="admin-users-form-actions"] [data-test="admin-users-form-cancel"]');

        $I->clickStable('[data-test="admin-users-form-cancel"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeInCurrentUrl('edit=');
    }

    // ─── Statistics Page ─────────────────────────────────────────

    /** @group admin */
    public function adminStatisticsPageDisplaysCorrectLayout(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin statistics page displays hero, year filter, and data table');

        $I->amOnPage('/admin/statistiky');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Hero card
        $I->seeElement('[data-test="admin-statistics-page"] .card');

        // Unit and year filters
        $I->seeElement('[data-test="admin-statistics-page"] select[name="unitId"]');
        $I->seeElement('[data-test="admin-statistics-page"] select[name="year"]');

        // Table card
        $I->seeElement('[data-test="admin-statistics-table-card"]');
        $I->seeElement('[data-test="admin-statistics-events-card"]');
        $I->seeElement('[data-test="admin-statistics-payments-card"]');
        $I->seeElement('[data-test="admin-statistics-invoices-card"]');
        $I->seeElement('[data-test="admin-statistics-bank-card"]');
        $I->seeElement('[data-test="admin-statistics-bug-reports-card"]');

        // Table has thead and tbody
        $I->seeElement('[data-test="admin-statistics-table-card"] table thead');
        $I->seeElement('[data-test="admin-statistics-table-card"] table tbody');

        // Table has content (may be empty in test environment)
        $I->seeElement('[data-test="admin-statistics-table-card"] table');
    }

    // ─── Card Link Navigation from Overview ──────────────────────

    /** @group admin */
    public function adminOverviewCardLinksNavigateCorrectly(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify clicking cards on admin overview navigates to the correct section');

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Click Users card link
        $I->clickStable('[data-test="admin-link-users"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/uzivatele');

        // Go back and click Statistics card link
        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="admin-link-statistics"]');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/statistiky');

        // Go back and click Bug reports card link
        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="admin-link-bug-reports"]');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/hlaseni-chyb');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function becomeAdmin(): void
    {
        $this->I->haveInDatabase('system_user_role', [
            'user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
            'role' => 'admin',
            'created_at' => '2026-03-19 12:00:00',
        ]);
    }
}
