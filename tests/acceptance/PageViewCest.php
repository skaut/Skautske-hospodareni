<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function array_diff;
use function array_values;

/**
 * The measurement that replaced the removed Google Analytics: counted on the
 * server, one row per page and day, with nothing stored in the browser.
 *
 * Public pages are enough to prove it, so these scenarios need no skautIS login.
 */
final class PageViewCest extends BaseAcceptanceCest
{
    private const PAGE_KEY = 'Default:privacy';
    private const PAGE_URL = '/zasady-soukromi';

    /** Everything the application is allowed to keep in a browser, all of it functional. */
    private const ALLOWED_BROWSER_KEYS = [
        'theme',
        'hskauting.sessionKeepAlive.lastPingAt',
        'hskauting.sessionKeepAlive.lockedUntil',
        'hskauting.appInstall.dismissedUntil',
    ];

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->deleteFromDatabase('page_view_daily', ['page_key' => self::PAGE_KEY]);
    }

    public function _after(AcceptanceTester $I): void
    {
        $I->deleteFromDatabase('page_view_daily', ['page_key' => self::PAGE_KEY]);
    }

    public function openingAPageCountsItWithoutTouchingTheBrowser(AcceptanceTester $I): void
    {
        $I->wantTo('have a page view counted on the server');

        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible('[data-test="privacy-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->seeInDatabase('page_view_daily', ['page_key' => self::PAGE_KEY, 'views' => 1]);

        // Nothing about the measurement may end up on the visitor's device: only the
        // keys the application needs for what the visitor asked for are allowed.
        $stored = $I->executeJS('return Object.keys(window.localStorage)');
        Assert::assertSame(
            [],
            array_values(array_diff($stored, self::ALLOWED_BROWSER_KEYS)),
            'Aplikace ukládá v prohlížeči neznámý klíč',
        );
    }

    public function aSecondVisitAddsToTheSameDay(AcceptanceTester $I): void
    {
        $I->wantTo('see repeated views of one day add up in one row');

        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible('[data-test="privacy-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->amOnPage('/');
        $I->waitForElementVisible('[data-test="homepage"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible('[data-test="privacy-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        Assert::assertSame(1, (int) $I->grabNumRecords('page_view_daily', ['page_key' => self::PAGE_KEY]));
        $I->seeInDatabase('page_view_daily', ['page_key' => self::PAGE_KEY, 'views' => 2]);
    }
}
