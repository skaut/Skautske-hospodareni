<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function date;
use function implode;
use function sprintf;

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

        $I->amOnPage('/admin/vyuziti');
        $I->waitForElement('.alert-danger', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/');
        $I->dontSeeElement('[data-test="admin-usage-page"]');
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

    // ─── CRUD: Create, Read, Update, Delete Admin User ───────────

    /** @group admin */
    public function adminUserCrudWorkflow(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $I->disablePopups();

        $I->wantTo('create, read, update, and delete an admin user');

        $I->amOnPage('/admin/uzivatele');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // ── CREATE ───────────────────────────────────────────────
        // Open form
        $I->clickStable('[data-test="admin-users-form-toggle"]');
        $I->waitForElementVisible('[data-test="admin-users-form"] input[name="userId"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Fill and submit
        $I->fillField('[data-test="admin-users-form"] input[name="userId"]', (string) self::NEW_ADMIN_USER_ID);
        $I->clickStable('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
        $I->clickStable('[data-test="admin-user-edit-'.$newUser.'"]');

        // Form should be visible with edit mode
        $I->waitForElementVisible('[data-test="admin-users-form-collapse"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-users-form"]');
        $I->seeElement('[data-test="admin-users-form-cancel"]');
        $I->seeInCurrentUrl('edit='.$newUser);

        // Change user_id
        $updatedUserId = self::NEW_ADMIN_USER_ID + 1;
        $I->fillField('[data-test="admin-users-form"] input[name="userId"]', (string) $updatedUserId);
        $I->clickStable('[data-test="admin-users-form"] input[type="submit"]');
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Verify update in DB
        $I->seeElement('.alert-success');
        $I->seeInDatabase('admin_user', ['user_id' => $updatedUserId]);
        $I->dontSeeInDatabase('admin_user', ['user_id' => self::NEW_ADMIN_USER_ID]);

        // ── DELETE ───────────────────────────────────────────────
        $I->waitForElementVisible('[data-test="admin-user-delete-'.$newUser.'"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->disablePopups();
        $I->clickStable('[data-test="admin-user-delete-'.$newUser.'"]');
        $I->waitForText('odebrán', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
        $I->waitForElementVisible('[data-test="admin-users-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Open form and try adding existing user_id
        $I->clickStable('[data-test="admin-users-form-toggle"]');
        $I->waitForElementVisible('[data-test="admin-users-form"] input[name="userId"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillFieldStable('[data-test="admin-users-form"] input[name="userId"]', (string) self::ACCEPTANCE_ADMIN_USER_ID);
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

        // Get our admin user ID
        $adminId = $I->grabFromDatabase('admin_user', 'id', ['user_id' => self::ACCEPTANCE_ADMIN_USER_ID]);

        $I->amOnPage('/admin/uzivatele?edit='.$adminId);
        $I->waitForElementVisible('[data-test="admin-users-form-collapse"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="admin-users-form-cancel"]');

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

    // ─── Usage Page ──────────────────────────────────────────────

    /**
     * Renders the page with real rows in `user_login`. Without them the template
     * stops at the "no data yet" notice and never reaches the tiles, breakdowns
     * and heat map — which is exactly where it used to fall over.
     *
     * @group admin
     */
    public function adminUsagePageRendersEveryCardWhenLoginsExist(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $this->haveLogins();

        $I->wantTo('verify admin usage page renders all cards from recorded logins');

        $I->amOnPage('/admin/vyuziti');
        $I->waitForElementVisible('[data-test="admin-usage-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // A Latte runtime error renders as a Tracy page, not as our markup.
        $I->dontSeeElement('#tracy-bs');
        $I->dontSee('Undefined variable');
        $I->dontSee('incompatible content type');

        // Filters
        $I->seeElement('[data-test="admin-usage-page"] select[name="unitId"]');
        $I->seeElement('[data-test="admin-usage-page"] select[name="year"]');

        // Every card
        $I->seeElement('[data-test="admin-usage-logins-card"]');
        $I->seeElement('[data-test="admin-usage-devices-card"]');
        $I->seeElement('[data-test="admin-usage-week-card"]');
        $I->seeElement('[data-test="admin-usage-preferences-card"]');
        $I->seeElement('[data-test="admin-usage-engagement-card"]');
        $I->seeElement('[data-test="admin-usage-monthly-card"]');

        // Data branch, not the empty-state notice
        $I->dontSeeElement('[data-test="admin-usage-no-data"]');
        $I->dontSeeElement('[data-test="admin-usage-tracking-unavailable"]');

        // The tiles that used to blow up on the |num filter
        $I->seeElement('[data-test="admin-usage-headline"] .usage-kpi__value');
        $I->seeNumberOfElements('[data-test="admin-usage-headline"] .usage-kpi', 4);

        // Formatted numbers actually made it into the markup
        $headline = $I->grabTextFrom('[data-test="admin-usage-headline"]');
        Assert::assertStringContainsString('Přihlášení', $headline);
        Assert::assertStringContainsString('Uživatelé', $headline);

        // Breakdown bars and the 7 x 24 heat map
        $I->seeElement('[data-test="admin-usage-devices-card"] .usage-bar__fill');

        // Twelve months of logins — not the domain counts Admin:Statistics owns.
        $I->seeNumberOfElements('[data-test="admin-usage-monthly-card"] .usage-bar', 12);
        $I->seeNumberOfElements('[data-test="admin-usage-week-card"] .usage-heatmap tbody tr', 7);
        $I->seeNumberOfElements('[data-test="admin-usage-week-card"] .usage-heatmap tbody tr:first-child td', 24);
    }

    /** @group admin */
    public function adminUsagePageExplainsItselfWithoutRecordedLogins(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $I->deleteFromDatabase('user_login', ['unit_id' => AcceptanceTester::UNIT_ID]);

        $I->wantTo('verify admin usage page stays readable before any login is recorded');

        $I->amOnPage('/admin/vyuziti');
        $I->waitForElementVisible('[data-test="admin-usage-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->dontSeeElement('#tracy-bs');
        $I->seeElement('[data-test="admin-usage-no-data"]');

        // Cards that do not depend on login tracking still render.
        $I->seeElement('[data-test="admin-usage-preferences-card"]');
        $I->seeElement('[data-test="admin-usage-engagement-card"]');

        // The login-only cards are hidden rather than shown empty.
        $I->dontSeeElement('[data-test="admin-usage-devices-card"]');
        $I->dontSeeElement('[data-test="admin-usage-week-card"]');
        $I->dontSeeElement('[data-test="admin-usage-monthly-card"]');
    }

    /** @group admin */
    public function adminUsagePageFitsAPhoneWithoutHorizontalOverflow(): void
    {
        $I = $this->I;
        $this->becomeAdmin();
        $this->haveLogins();

        $I->wantTo('verify the usage page does not overflow sideways on a phone');

        $I->resizeWindow(375, 900);
        $I->amOnPage('/admin/vyuziti');
        $I->waitForElementVisible('[data-test="admin-usage-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // The heat map and the month table have to scroll inside their own
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

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->seeElement('[data-test="admin-card-usage"].navigation-card');
        $usageHref = $I->grabAttributeFrom('[data-test="admin-link-usage"]', 'href');
        Assert::assertStringContainsString('/admin/vyuziti', $usageHref);

        $I->clickStable('[data-test="admin-nav-usage"]');
        $I->waitForElementVisible('[data-test="admin-usage-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/vyuziti');
        $I->seeElement('[data-test="admin-nav-usage"].btn-primary');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Spread over two weekdays and two browsers so every breakdown has something to show. */
    private function haveLogins(): void
    {
        $I = $this->I;
        $year = (int) date('Y');

        $I->deleteFromDatabase('user_login', ['unit_id' => AcceptanceTester::UNIT_ID]);

        $rows = [
            // Monday morning, desktop, ended by logout
            [1, $year.'-01-05 09:00:00', $year.'-01-05 09:42:00', 'logout', 'desktop', 'Chrome', '130', 'Windows'],
            [1, $year.'-01-05 14:00:00', $year.'-01-05 14:05:00', null, 'desktop', 'Chrome', '130', 'Windows'],
            [2, $year.'-01-06 20:00:00', $year.'-01-06 20:30:00', null, 'mobile', 'Safari', null, 'iOS'],
            [3, $year.'-01-06 21:00:00', $year.'-01-06 21:10:00', null, 'tablet', 'Firefox', '131', 'Android'],
        ];

        foreach ($rows as [$userId, $loggedInAt, $lastSeenAt, $endReason, $deviceType, $browser, $browserVersion, $platform]) {
            $I->haveInDatabase('user_login', [
                'user_id' => $userId,
                'unit_id' => AcceptanceTester::UNIT_ID,
                'role_id' => 1,
                'role_key' => 'vedouciStredisko',
                'logged_in_at' => $loggedInAt,
                'last_seen_at' => $lastSeenAt,
                'logged_out_at' => $endReason === null ? null : $lastSeenAt,
                'end_reason' => $endReason,
                'device_type' => $deviceType,
                'browser' => $browser,
                'browser_version' => $browserVersion,
                'platform' => $platform,
            ]);
        }
    }

    private function becomeAdmin(): void
    {
        $this->I->haveInDatabase('admin_user', [
            'user_id' => self::ACCEPTANCE_ADMIN_USER_ID,
            'created_at' => '2026-03-19 12:00:00',
        ]);
    }
}
