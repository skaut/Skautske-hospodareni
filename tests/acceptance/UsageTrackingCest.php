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

    public function switchingTheThemeIsStoredWithTheLogin(AcceptanceTester $I): void
    {
        $I->wantTo('have the theme a user switched to stored for the share of dark mode');

        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $this->openPageAndWaitForElementWithSkautisRetry($I, self::PAGE_URL, '[data-test="dashboard"]');

        if ($I->executeJS('return document.documentElement.dataset.bsTheme') === 'dark') {
            $I->clickStable('[data-test="dark-mode-toggle"]');
            sleep(2);
        }

        $before = $this->darkLogins($I);

        $I->clickStable('[data-test="dark-mode-toggle"]');
        $afterDark = $this->waitForDarkLogins($I, static fn (int $count): bool => $count > $before);

        // Back to the light theme, so the scenarios after this one see the pages as before.
        $I->clickStable('[data-test="dark-mode-toggle"]');
        $afterLight = $this->waitForDarkLogins($I, static fn (int $count): bool => $count === $before);

        Assert::assertGreaterThan($before, $afterDark, 'Přepnutí na tmavý režim se k přihlášení neuložilo');
        Assert::assertSame($before, $afterLight, 'Přepnutí zpět na světlý režim se k přihlášení neuložilo');
    }

    private function darkLogins(AcceptanceTester $I): int
    {
        return (int) $I->grabNumRecords('user_login', ['dark_mode' => 1]);
    }

    /** @param callable(int): bool $done */
    private function waitForDarkLogins(AcceptanceTester $I, callable $done): int
    {
        $count = $this->darkLogins($I);

        for ($second = 0; $second < self::THEME_REPORT_WAIT_SECONDS && ! $done($count); ++$second) {
            sleep(1);
            $count = $this->darkLogins($I);
        }

        return $count;
    }

    private function pageSessions(AcceptanceTester $I, string $day): int
    {
        return array_sum(array_map('intval', $I->grabColumnFromDatabase('usage_session_daily', 'sessions', ['day' => $day, 'page' => self::PAGE])));
    }
}
