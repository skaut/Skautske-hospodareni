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
        $I->waitForElement('.alert-danger', 10);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-page"]');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElement('.alert-danger', 10);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-users-page"]');

        $I->amOnPage('/admin/statistiky');
        $I->waitForElement('.alert-danger', 10);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-statistics-page"]');

        $I->amOnPage('/admin/hlaseni-chyb');
        $I->waitForElement('.alert-danger', 10);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-bug-reports-page"]');
    }

    // ─── Overview Page ───────────────────────────────────────────

    /** @group admin */
    public function adminOverviewDisplaysCardsAndLinksCorrectly(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin overview page shows cards with correct links');

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', 10);

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
        $I->waitForElementVisible('[data-test="admin-page"]', 10);
        $I->seeElement('[data-test="admin-nav-overview"].btn-primary');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Users active
        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);
        $I->seeElement('[data-test="admin-nav-users"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Statistics active
        $I->amOnPage('/admin/statistiky');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', 10);
        $I->seeElement('[data-test="admin-nav-statistics"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');

        // Bug reports active
        $I->amOnPage('/admin/hlaseni-chyb');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', 10);
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
        $I->waitForElementVisible('[data-test="admin-page"]', 10);

        // Click Users pill
        $I->click('[data-test="admin-nav-users"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);
        $I->seeInCurrentUrl('/admin/uzivatele');

        // Click Statistics pill
        $I->click('[data-test="admin-nav-statistics"]');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', 10);
        $I->seeInCurrentUrl('/admin/statistiky');

        // Click Bug reports pill
        $I->click('[data-test="admin-nav-bug-reports"]');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', 10);
        $I->seeInCurrentUrl('/admin/hlaseni-chyb');

        // Click Overview pill (back)
        $I->click('[data-test="admin-nav-overview"]');
        $I->waitForElementVisible('[data-test="admin-page"]', 10);
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
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);

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

    // ─── CRUD: Create, Read, Update, Delete Admin User ───────────

    /** @group admin */
    public function adminUserCrudWorkflow(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $I->disablePopups();

        $I->wantTo('create, read, update, and delete an admin user');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);

        // ── CREATE ───────────────────────────────────────────────
        // Open form
        $I->click('[data-test="admin-users-form-toggle"]');
        $I->waitForElementVisible('[data-test="admin-users-form"] input[name="userId"]', 10);

        // Fill and submit
        $I->fillField('[data-test="admin-users-form"] input[name="userId"]', (string) self::NEW_ADMIN_USER_ID);
        $I->click('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);

        // Verify flash message
        $I->seeElement('.alert-success');

        // ── READ ─────────────────────────────────────────────────
        // Verify user appears in the list
        $I->seeElement('[data-test="admin-users-list"]');
        $I->seeInDatabase('admin_user', ['user_id' => self::NEW_ADMIN_USER_ID]);

        // Verify at least 2 rows (self + new user)
        $I->seeNumberOfElements('[data-test="admin-users-list"] tbody tr', [2, 100]);

        // ── UPDATE ───────────────────────────────────────────────
        // Find the new user's row and click edit
        $newUser = $I->grabFromDatabase('admin_user', 'id', ['user_id' => self::NEW_ADMIN_USER_ID]);
        $I->seeElement('[data-test="admin-user-edit-'.$newUser.'"]');
        $I->click('[data-test="admin-user-edit-'.$newUser.'"]');

        // Form should be visible with edit mode
        $I->waitForElementVisible('[data-test="admin-users-form-collapse"]', 10);
        $I->seeElement('[data-test="admin-users-form"]');
        $I->seeElement('[data-test="admin-users-form-cancel"]');
        $I->seeInCurrentUrl('edit='.$newUser);

        // Change user_id
        $updatedUserId = self::NEW_ADMIN_USER_ID + 1;
        $I->fillField('[data-test="admin-users-form"] input[name="userId"]', (string) $updatedUserId);
        $I->click('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);

        // Verify update in DB
        $I->seeElement('.alert-success');
        $I->seeInDatabase('admin_user', ['user_id' => $updatedUserId]);
        $I->dontSeeInDatabase('admin_user', ['user_id' => self::NEW_ADMIN_USER_ID]);

        // ── DELETE ───────────────────────────────────────────────
        $I->waitForElementVisible('[data-test="admin-user-delete-'.$newUser.'"]', 10);
        $I->disablePopups();
        $I->click('[data-test="admin-user-delete-'.$newUser.'"]');
        $I->waitForText('odebrán', 15);
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);

        // Verify deletion
        $I->seeElement('.alert-success');
        $I->dontSeeInDatabase('admin_user', ['user_id' => $updatedUserId]);
    }

    /** @group admin */
    public function adminUserCreateRejectsDuplicateUserId(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify that creating admin with duplicate user_id is rejected');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);

        // Open form and try adding existing user_id
        $I->clickStable('[data-test="admin-users-form-toggle"]');
        $I->waitForElementVisible('[data-test="admin-users-form"] input[name="userId"]', 10);

        $I->fillFieldStable('[data-test="admin-users-form"] input[name="userId"]', (string) self::ACCEPTANCE_ADMIN_USER_ID);
        $I->clickStable('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForJS('return document.querySelector(".alert-warning") !== null;', 10);

        // Should show warning
        $I->seeElement('.alert-warning');
    }

    /** @group admin */
    public function adminUserEditCancelReturnsToDefaultView(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify cancelling edit returns to the default users view');

        // Get our admin user ID
        $adminId = $I->grabFromDatabase('admin_user', 'id', ['user_id' => self::ACCEPTANCE_ADMIN_USER_ID]);

        $I->amOnPage('/admin/uzivatele?edit='.$adminId);
        $I->waitForElementVisible('[data-test="admin-users-form-collapse"]', 10);
        $I->seeElement('[data-test="admin-users-form-cancel"]');

        $I->click('[data-test="admin-users-form-cancel"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);
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
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', 10);

        // Hero card
        $I->seeElement('[data-test="admin-statistics-page"] .card');

        // Table card
        $I->seeElement('[data-test="admin-statistics-table-card"]');

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
        $I->waitForElementVisible('[data-test="admin-page"]', 10);

        // Click Users card link
        $I->click('[data-test="admin-link-users"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', 10);
        $I->seeInCurrentUrl('/admin/uzivatele');

        // Go back and click Statistics card link
        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', 10);
        $I->click('[data-test="admin-link-statistics"]');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', 10);
        $I->seeInCurrentUrl('/admin/statistiky');

        // Go back and click Bug reports card link
        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', 10);
        $I->click('[data-test="admin-link-bug-reports"]');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', 10);
        $I->seeInCurrentUrl('/admin/hlaseni-chyb');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function becomeAdmin(): void
    {
        $this->I->haveInDatabase('admin_user', [
            'user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
            'created_at' => '2026-03-19 12:00:00',
        ]);
    }
}
