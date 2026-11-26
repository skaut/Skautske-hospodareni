<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function array_diff;
use function array_map;
use function array_sum;
use function array_values;
use function date;
use function sleep;

/**
 * The measurement behind Admin:Usage: sessions counted on the server, only sums without the user,
 * with nothing stored in the browser.
 */
final class UsageTrackingCest extends BaseAcceptanceCest
{
    private const PAGE = 'Dashboard:default';
    private const PAGE_URL = '/nastenka';

    /** Everything the application is allowed to keep in a browser, all of it functional. */
    private const ALLOWED_BROWSER_KEYS = [
        'theme',
        'hskauting.sessionKeepAlive.lastPingAt',
        'hskauting.sessionKeepAlive.lockedUntil',
        'hskauting.appInstall.dismissedUntil',
    ];

    private const THEME_REPORT_WAIT_SECONDS = 10;

    public function aSignedInPageCountsOncePerSessionWithoutTouchingTheBrowser(AcceptanceTester $I): void
    {
        $I->wantTo('have a session counted on the server once per day in the system and under the unit of the role');

        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $this->openPageAndWaitForElementWithSkautisRetry($I, self::PAGE_URL, '[data-test="dashboard"]');

        $today = date('Y-m-d');
        // The whole system down to the page, and the unit of the role down to the screen.
        $I->seeInDatabase('usage_session_daily', ['day' => $today, 'unit_code' => '', 'section' => '', 'screen' => '', 'page' => '']);
        $I->seeInDatabase('usage_session_daily', ['day' => $today, 'unit_code' => '', 'section' => 'other', 'screen' => '', 'page' => '']);
        $I->seeInDatabase('usage_session_daily', ['day' => $today, 'unit_code' => '', 'section' => 'other', 'screen' => 'Dashboard', 'page' => self::PAGE]);
        $I->seeInDatabase('usage_session_daily', ['day' => $today, 'unit_code !=' => '', 'section' => 'other', 'screen' => 'Dashboard', 'page' => '']);
        $I->dontSeeInDatabase('usage_session_daily', ['day' => $today, 'unit_code !=' => '', 'page' => self::PAGE]);

        $before = $this->pageSessions($I, $today);
        $this->openPageAndWaitForElementWithSkautisRetry($I, self::PAGE_URL, '[data-test="dashboard"]');
        Assert::assertSame($before, $this->pageSessions($I, $today), 'Návrat na stránku ve stejném sezení se započítal znovu');

        // Nothing about the measurement may end up on the visitor's device: only the
        // keys the application needs for what the visitor asked for are allowed.
        $stored = $I->executeJS('return Object.keys(window.localStorage)');
        Assert::assertSame(
            [],
            array_values(array_diff($stored, self::ALLOWED_BROWSER_KEYS)),
            'Aplikace ukládá v prohlížeči neznámý klíč',
        );
    }

    public function publicPagesAreNotCounted(AcceptanceTester $I): void
    {
        $I->wantTo('see that pages for visitors who are not signed in are not counted');

        $I->amOnPage('/zasady-soukromi');
        $I->waitForElementVisible('[data-test="privacy-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->dontSeeInDatabase('usage_session_daily', ['page' => 'Default:privacy']);
    }

    public function thePageReportsTheThemeOfTheLogin(AcceptanceTester $I): void
    {
        $I->wantTo('have the theme of a login stored for the share of dark mode');

        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $this->openPageAndWaitForElementWithSkautisRetry($I, self::PAGE_URL, '[data-test="dashboard"]');

        $reported = 0;
        for ($second = 0; $second < self::THEME_REPORT_WAIT_SECONDS && $reported === 0; ++$second) {
            $reported = (int) $I->grabNumRecords('user_login', ['dark_mode !=' => null]);

            if ($reported === 0) {
                sleep(1);
            }
        }

        Assert::assertGreaterThan(0, $reported, 'Stránka nenahlásila tmavý režim přihlášení');
    }

    private function pageSessions(AcceptanceTester $I, string $day): int
    {
        return array_sum(array_map('intval', $I->grabColumnFromDatabase('usage_session_daily', 'sessions', ['day' => $day, 'page' => self::PAGE])));
    }
}
