<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function date;
use function hash;
use function implode;
use function sprintf;
use function strtotime;

final class AdminCest extends BaseAcceptanceCest
{
    private const ACCEPTANCE_ADMIN_USER_ID = 2465;
    private const NEW_ADMIN_USER_ID = 9999;

    /** Jihomoravský kraj and in it an okres no real login produces, see haveSessions(). */
    private const USAGE_REGION = '620';
    private const USAGE_DISTRICT = '62Z';

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

        $I->deleteFromDatabase('system_user_role', ['user_id' => self::ACCEPTANCE_ADMIN_USER_ID]);
        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->wantTo('verify that non-admin user cannot see or open admin section');

        $I->dontSeeElement('[data-test="utility-nav-admin"]');

        $I->amOnPage('/admin');
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-page"]');

        $I->amOnPage('/admin/uzivatele');
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-users-page"]');

        $I->amOnPage('/admin/statistiky');
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-statistics-page"]');

        $I->amOnPage('/admin/hlaseni-chyb');
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-bug-reports-page"]');

        $I->amOnPage('/admin/usage');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-usage-page"]');
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
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-users-page"]');

        $I->amOnPage('/admin/statistiky');
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-statistics-page"]');
    }

    // ─── Overview Page ───────────────────────────────────────────

    /** @group admin */
    public function adminOverviewDisplaysCardsAndLinksCorrectly(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify admin overview page shows cards with correct links');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');

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
        Assert::assertStringContainsString('/admin/uzivatele', (string) $usersHref);

        $I->seeElement('[data-test="admin-link-statistics"].stretched-link');
        $statsHref = $I->grabAttributeFrom('[data-test="admin-link-statistics"]', 'href');
        Assert::assertStringContainsString('/admin/statistiky', (string) $statsHref);

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
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');
        $I->seeElement('[data-test="admin-nav-overview"].btn-primary');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Users active
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/uzivatele', '[data-test="admin-users-page"]');
        $I->seeElement('[data-test="admin-nav-users"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-light');
        $I->seeElement('[data-test="admin-nav-bug-reports"].btn-light');

        // Statistics active
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/statistiky', '[data-test="admin-statistics-page"]');
        $I->seeElement('[data-test="admin-nav-statistics"].btn-primary');
        $I->seeElement('[data-test="admin-nav-overview"].btn-light');
        $I->seeElement('[data-test="admin-nav-users"].btn-light');

        // Bug reports active
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/hlaseni-chyb', '[data-test="admin-bug-reports-page"]');
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

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');

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

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/uzivatele', '[data-test="admin-users-page"]');

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

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/uzivatele', '[data-test="admin-users-page"]');

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
        $I->seeNumberOfElements('[data-test="admin-users-role-options"] .form-check', 3);
        $I->seeElement('input[name="roles[]"][value="announcement_manager"]');
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

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/uzivatele', '[data-test="admin-users-page"]');

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

        $this->openPageAndWaitForElementWithSkautisRetry(
            $I,
            '/admin/uzivatele?edit='.self::ACCEPTANCE_ADMIN_USER_ID,
            '[data-test="admin-users-form-collapse"]',
        );
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

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/statistiky', '[data-test="admin-statistics-page"]');

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

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');

        // Click Users card link
        $I->clickStable('[data-test="admin-link-users"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/uzivatele');

        // Go back and click Statistics card link
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');
        $I->clickStable('[data-test="admin-link-statistics"]');
        $I->waitForElementVisible('[data-test="admin-statistics-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/statistiky');

        // Go back and click Bug reports card link
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');
        $I->clickStable('[data-test="admin-link-bug-reports"]');
        $I->waitForElementVisible('[data-test="admin-bug-reports-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/hlaseni-chyb');
    }

    // ─── Usage Page ──────────────────────────────────────────────

    /**
     * Renders the page with real rows in `user_login` and `usage_session_daily`. Without them the template
     * stops at the "no data yet" notices and never reaches the tables, breakdowns and heat map.
     *
     * @group admin
     */
    public function adminUsagePageShowsTheWholeSystemWithItsKraje(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $this->haveLogins();
        $this->haveSessions();

        $I->wantTo('verify admin usage page shows sections and kraje of the whole system');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/usage', '[data-test="admin-usage-page"]');

        // A Latte runtime error renders as a Tracy page, not as our markup.
        $I->dontSeeElement('#tracy-bs');
        $I->dontSee('Undefined variable');
        $I->dontSee('incompatible content type');

        // The administrator always sees the whole system: no unit to pick.
        $I->dontSeeElement('[data-test="admin-usage-page"] select[name="unitId"]');
        $I->seeElement('[data-test="admin-usage-page"] select[name="year"]');
        $I->seeElement('[data-test="admin-usage-page"] select[name="period"]');
        $I->see('Celý systém', '[data-test="admin-usage-scope"]');

        $I->seeNumberOfElements('[data-test="admin-usage-headline"] .usage-kpi', 4);
        $headline = $I->grabTextFrom('[data-test="admin-usage-headline"]');
        Assert::assertStringContainsString('Sezení', $headline);
        Assert::assertStringContainsString('Uživatelé', $headline);

        // Sections with their screens, kraje with a link to their units.
        $I->seeElement('[data-test="admin-usage-section-payments"]');
        $I->seeElement('[data-test="admin-usage-row-'.self::USAGE_REGION.'"]');
        $I->seeElement('[data-test="admin-usage-row-ustredi"]');
        $I->dontSeeElement('[data-test="admin-usage-open-ustredi"]');
        $I->clickStable('[data-test="admin-usage-section-toggle-payments"]');
        $I->waitForElementVisible('#usage-screens-payments', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Seznam skupin', '#usage-screens-payments');

        // The general overview of logins.
        $I->dontSeeElement('[data-test="admin-usage-no-data"]');
        $I->seeElement('[data-test="admin-usage-logins-card"]');
        $I->seeElement('[data-test="admin-usage-devices-card"]');
        $I->seeElement('[data-test="admin-usage-week-card"]');
        $I->seeElement('[data-test="admin-usage-preferences-card"]');
        $I->seeNumberOfElements('[data-test="admin-usage-preferences"] .usage-kpi', 4);
        $I->see('Na pozadí prodlužovat přihlášení ke skautISu', '[data-test="admin-usage-preferences"]');
        $I->seeNumberOfElements('[data-test="admin-usage-automation"] .usage-kpi', 2);
        $I->see('Automatické upomínky plateb', '[data-test="admin-usage-automation"]');

        // Two of the seeded users switched the theme with the button, the last choice of one was dark.
        $I->seeElement('[data-test="admin-usage-dark-mode-meter"][role="meter"]');
        $I->seeElement('[data-test="admin-usage-monthly-card"]');
        $I->dontSeeElement('[data-test="admin-usage-engagement-card"]');
        $I->seeElement('[data-test="admin-usage-devices-card"] .usage-bar__fill');
        $I->seeNumberOfElements('[data-test="admin-usage-monthly-card"] .usage-bar', 12);
        $I->seeNumberOfElements('[data-test="admin-usage-week-card"] .usage-heatmap tbody tr', 7);
        $I->seeNumberOfElements('[data-test="admin-usage-week-card"] .usage-heatmap tbody tr:first-child td', 24);
    }

    /** @group admin */
    public function adminUsagePageOpensAKrajAndItsOkres(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $this->haveSessions();

        $I->wantTo('open a kraj with its okresy and an okres with its střediska');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/usage', '[data-test="admin-usage-page"]');
        $I->clickStable('[data-test="admin-usage-open-'.self::USAGE_REGION.'"]');
        $I->waitForElementVisible('[data-test="admin-usage-breadcrumbs"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Kraj: its okresy.
        $I->see('Jihomoravský kraj TGM', '[data-test="admin-usage-scope"]');
        $I->seeElement('[data-test="admin-usage-open-'.self::USAGE_DISTRICT.'"]');
        $I->dontSeeElement('[data-test="admin-usage-row-ustredi"]');
        $I->seeNumberOfElements('[data-test="admin-usage-headline"] .usage-kpi', 2);

        // The general overview belongs to the whole system only.
        $I->dontSeeElement('[data-test="admin-usage-logins-card"]');
        $I->dontSeeElement('[data-test="admin-usage-preferences-card"]');

        // Okres: its střediska and the roles of the okres itself.
        $I->clickStable('[data-test="admin-usage-open-'.self::USAGE_DISTRICT.'"]');
        $I->waitForText('Okres '.self::USAGE_DISTRICT, AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="admin-usage-scope"]');
        $I->seeElement('[data-test="admin-usage-row-'.self::USAGE_DISTRICT.'.99"]');
        $I->see(self::USAGE_DISTRICT.' (role okresu)', '[data-test="admin-usage-row-'.self::USAGE_DISTRICT.'"]');

        // Back to the kraj, then to the whole system.
        $I->clickStable('[data-test="admin-usage-back-2"]');
        $I->waitForElementVisible('[data-test="admin-usage-open-'.self::USAGE_DISTRICT.'"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="admin-usage-back-1"]');
        $I->waitForElementVisible('[data-test="admin-usage-logins-card"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @group admin */
    public function adminUsagePageShowsTheYearByMonths(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $this->haveSessions();

        $I->wantTo('see the year by months with a thirteenth column for the total');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/usage', '[data-test="admin-usage-page"]');
        $I->selectOption('[data-test="admin-usage-filter"] select[name="period"]', 'months');
        $I->waitForElementVisible('[data-test="admin-usage-filter"] select[name="section"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->selectOption('[data-test="admin-usage-filter"] select[name="section"]', 'payments');
        $I->clickStable('[data-test="admin-usage-filter"] input[type="submit"]');
        $I->waitForText('po měsících', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="admin-usage-scope"]');

        $I->seeNumberOfElements('[data-test="admin-usage-sections-card"] thead th', 14);
        $I->seeNumberOfElements('[data-test="admin-usage-rows-card"] thead th', 14);
        $I->see('Platby', '[data-test="admin-usage-rows-card"]');
    }

    /** @group admin */
    public function adminUsagePageExplainsAnEmptyMonth(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $year = (int) date('Y');
        $month = (int) date('n') % 12 + 1;
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-d', (int) strtotime($from.' +1 month'));
        $I->deleteFromDatabase('user_login', ['logged_in_at >=' => $from, 'logged_in_at <' => $to]);
        $I->deleteFromDatabase('usage_session_daily', ['day >=' => $from, 'day <' => $to]);

        $I->wantTo('verify admin usage page stays readable for a month without data');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/usage?period=month&month='.$month, '[data-test="admin-usage-page"]');

        $I->dontSeeElement('#tracy-bs');
        $I->seeElement('[data-test="admin-usage-no-data"]');
        $I->seeElement('[data-test="admin-usage-no-logins"]');
        $I->seeElement('[data-test="admin-usage-preferences-card"]');

        // Tables and login-only cards are hidden rather than shown empty.
        $I->dontSeeElement('[data-test="admin-usage-sections-card"]');
        $I->dontSeeElement('[data-test="admin-usage-rows-card"]');
        $I->dontSeeElement('[data-test="admin-usage-devices-card"]');
        $I->dontSeeElement('[data-test="admin-usage-week-card"]');
        $I->dontSeeElement('[data-test="admin-usage-monthly-card"]');
    }

    /** @group admin */
    public function adminUsagePageRejectsAnUnknownKraj(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('get a 404 for a kraj that is not a Junák kraj');

        $I->amOnPage('/admin/usage?region=621');
        $I->waitForText('404', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @group admin */
    public function adminUsagePageFitsAPhoneWithoutHorizontalOverflow(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $this->haveLogins();
        $this->haveSessions();

        $I->wantTo('verify the usage page does not overflow sideways on a phone');

        $I->resizeWindow(375, 900);
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/usage', '[data-test="admin-usage-page"]');

        // The heat map and the wide tables have to scroll inside their own
        // container. Naming the widest element turns a failure here into a
        // pointer instead of a hunt.
        $measured = $I->executeJS(<<<'JS'
const limit = document.documentElement.clientWidth;
const offenders = [];

document.querySelectorAll('[data-test="admin-usage-page"] *').forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.right <= limit + 1 || rect.width === 0) { return; }

    // Anything inside a horizontal scroll container is meant to be wider.
    let ancestor = el.parentElement, contained = false;
    while (ancestor && ancestor !== document.body) {
        const overflowX = getComputedStyle(ancestor).overflowX;
        if (overflowX === 'auto' || overflowX === 'scroll' || overflowX === 'hidden') { contained = true; break; }
        ancestor = ancestor.parentElement;
    }
    if (contained) { return; }

    offenders.push(
        el.tagName.toLowerCase()
        + '.' + (el.className || '').toString().split(' ').slice(0, 2).join('.')
        + ' (' + Math.round(rect.width) + 'px)'
    );
});

return {
    overflow: document.documentElement.scrollWidth - limit,
    offenders: offenders.slice(0, 5),
};
JS);

        Assert::assertLessThanOrEqual(
            1,
            (int) $measured['overflow'],
            sprintf(
                'Stránka Využití přetéká vodorovně na 375 px o %d px. Nejširší prvky: %s',
                (int) $measured['overflow'],
                implode(', ', $measured['offenders']) ?: 'neurčeno',
            ),
        );

        $I->resizeWindow(self::DEFAULT_WINDOW_WIDTH, self::DEFAULT_WINDOW_HEIGHT);
    }

    /** @group admin */
    public function adminUsagePageIsReachableFromOverviewAndSubmenu(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $I->wantTo('verify the usage page is linked from the admin overview and submenu');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin', '[data-test="admin-page"]');

        $I->seeElement('[data-test="admin-card-usage"].navigation-card');
        $usageHref = $I->grabAttributeFrom('[data-test="admin-link-usage"]', 'href');
        Assert::assertStringContainsString('/admin/usage', (string) $usageHref);
        Assert::assertStringNotContainsString('unitId', (string) $usageHref);

        $I->clickStable('[data-test="admin-nav-usage"]');
        $I->waitForElementVisible('[data-test="admin-usage-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/usage');
        $I->seeElement('[data-test="admin-nav-usage"].btn-primary');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Spread over two weekdays and two browsers so every breakdown has something to show. */
    // ─── Cron ────────────────────────────────────────────────────

    /** @group admin */
    public function adminCanPauseResumeAndRescheduleCronJob(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $I->deleteFromDatabase('cron_job', ['name' => 'paymentPairing']);

        $I->wantTo('pause, resume and change the interval of a cron job');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/cron', '[data-test="admin-cron-job-paymentPairing"]');
        $I->seeElement('[data-test="admin-nav-cron"].btn-primary');
        $I->see('Aktivní', '[data-test="admin-cron-state-paymentPairing"]');
        $I->see('každé 2 hodiny', '[data-test="admin-cron-interval-paymentPairing"]');

        $I->clickStable('[data-test="admin-cron-pause-paymentPairing"]');
        $I->waitForElementVisible('[data-test="admin-cron-resume-paymentPairing"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('.alert-success');
        $I->see('Pozastaveno', '[data-test="admin-cron-state-paymentPairing"]');
        $I->seeInDatabase('cron_job', ['name' => 'paymentPairing', 'paused' => 1]);

        $I->clickStable('[data-test="admin-cron-resume-paymentPairing"]');
        $I->waitForElementVisible('[data-test="admin-cron-pause-paymentPairing"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Aktivní', '[data-test="admin-cron-state-paymentPairing"]');
        $I->seeInDatabase('cron_job', ['name' => 'paymentPairing', 'paused' => 0]);

        $I->selectOption('[data-test="admin-cron-schedule-paymentPairing"]', 'každou hodinu');
        $I->clickStable('[data-test="admin-cron-schedule-save-paymentPairing"]');
        $I->waitForText('každou hodinu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="admin-cron-interval-paymentPairing"]');
        $I->seeInDatabase('cron_job', ['name' => 'paymentPairing', 'schedule' => '0 * * * *']);

        $I->deleteFromDatabase('cron_job', ['name' => 'paymentPairing']);
    }

    /** @group admin */
    public function adminSeesTaskQueueOverviewAndAutomationUsage(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $runId = $I->haveInDatabase('task_queue_run', [
            'started_at' => date('Y-m-d H:i:s', time() - 120),
            'finished_at' => date('Y-m-d H:i:s', time() - 110),
            'taken' => 5,
            'done' => 4,
            'skipped' => 0,
            'failed' => 1,
            'uncertain' => 0,
            'postponed' => 0,
            'recovered' => 0,
            'stop_reason' => 'empty',
            'pending_after' => 0,
        ]);

        $I->wantTo('see the task queue and how much the automation is used');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/admin/cron', '[data-test="admin-cron-usage-taskQueue"]');
        $I->seeElement('[data-test="admin-cron-usage-paymentPairing"]');
        $I->seeElement('[data-test="admin-cron-usage-paymentReminders"]');
        $I->clickStable('[data-test="admin-cron-usage-taskQueue"] a');

        $I->waitForElementVisible('[data-test="admin-task-queue-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-nav-task-queue"].btn-primary');
        $I->see('E-maily plateb', '[data-test="admin-task-queue-types"]');
        $I->see('fronta je prázdná', '[data-test="admin-task-queue-run-'.$runId.'"]');
        $I->seeNoHorizontalOverflowOnPhone('fronta úloh');

        $I->deleteFromDatabase('task_queue_run', ['id' => $runId]);
    }

    private function haveLogins(): void
    {
        $I = $this->I;
        $year = (int) date('Y');

        // Users made up for the scenario: the hashes no real login produces.
        $rows = [
            // Monday morning, desktop, ended by logout
            [1, $year.'-01-05 09:00:00', $year.'-01-05 09:42:00', 'logout', 'desktop', 'Chrome', '130', 'Windows', 0],
            [1, $year.'-01-05 14:00:00', $year.'-01-05 14:05:00', null, 'desktop', 'Chrome', '130', 'Windows', 1],
            [2, $year.'-01-06 20:00:00', $year.'-01-06 20:30:00', null, 'mobile', 'Safari', null, 'iOS', null],
            [3, $year.'-01-06 21:00:00', $year.'-01-06 21:10:00', null, 'tablet', 'Firefox', '131', 'Android', 0],
        ];

        foreach ($rows as [$userId, $loggedInAt, $lastSeenAt, $endReason, $deviceType, $browser, $browserVersion, $platform, $darkMode]) {
            $userHash = hash('sha256', 'acceptance-user:'.$userId);
            $I->deleteFromDatabase('user_login', ['user_hash' => $userHash, 'logged_in_at' => $loggedInAt]);
            $I->haveInDatabase('user_login', [
                'user_hash' => $userHash,
                'logged_in_at' => $loggedInAt,
                'last_seen_at' => $lastSeenAt,
                'logged_out_at' => $endReason === null ? null : $lastSeenAt,
                'end_reason' => $endReason,
                'device_type' => $deviceType,
                'browser' => $browser,
                'browser_version' => $browserVersion,
                'platform' => $platform,
                'dark_mode' => $darkMode,
            ]);
        }
    }

    /**
     * Sessions under a kraj no real login produces, on a day of this year the tracker does not write to while
     * the tests run, so the scenario never collides with what it counts for the test user.
     */
    private function haveSessions(): void
    {
        $I = $this->I;
        $year = date('Y');
        $day = $year.'-01-02' === date('Y-m-d') ? $year.'-01-03' : $year.'-01-02';
        $I->deleteFromDatabase('usage_session_daily', ['day' => $day]);

        // The whole system: each login once, down to the page.
        $this->haveSessionRows($day, '', [
            ['', '', '', 6],
            ['payments', '', '', 4],
            ['payments', 'Payments:GroupList', '', 4],
            ['payments', 'Payments:GroupList', 'Payments:GroupList:default', 4],
            ['travel', '', '', 3],
            ['travel', 'Travel:Command', '', 3],
            ['travel', 'Travel:Command', 'Travel:Command:detail', 3],
        ]);

        // Units of the roles, down to the screen.
        foreach ([self::USAGE_DISTRICT, self::USAGE_DISTRICT.'.99', 'ustredi'] as $unitCode) {
            $this->haveSessionRows($day, $unitCode, [
                ['', '', '', 2],
                ['payments', '', '', 2],
                ['payments', 'Payments:GroupList', '', 2],
                ['travel', '', '', 1],
                ['travel', 'Travel:Command', '', 1],
            ]);
        }
    }

    /** @param list<array{string, string, string, int}> $rows section, screen, page, sessions */
    private function haveSessionRows(string $day, string $unitCode, array $rows): void
    {
        foreach ($rows as [$section, $screen, $page, $sessions]) {
            $this->I->haveInDatabase('usage_session_daily', [
                'day' => $day,
                'unit_code' => $unitCode,
                'section' => $section,
                'screen' => $screen,
                'page' => $page,
                'sessions' => $sessions,
            ]);
        }
    }

    private function becomeAdmin(): void
    {
        $this->I->haveInDatabase('system_user_role', [
            'user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
            'role' => 'admin',
            'created_at' => '2026-03-19 12:00:00',
        ]);
    }
}
