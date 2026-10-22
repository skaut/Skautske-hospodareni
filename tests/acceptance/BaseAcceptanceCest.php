<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Exception;
use PHPUnit\Framework\Assert;
use SkautisWsdlPageException;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
abstract class BaseAcceptanceCest
{
    /** Matches `--window-size` in acceptance.suite.yml. */
    protected const DEFAULT_WINDOW_WIDTH = 1920;
    protected const DEFAULT_WINDOW_HEIGHT = 1080;

    protected const WEBDRIVER_WARMUP_ATTEMPTS = 3;
    protected const SKAUTIS_PAGE_OPEN_ATTEMPTS = 3;
    protected const SKAUTIS_PAGE_OPEN_RETRY_DELAY_SECONDS = 2;

    public function _before(AcceptanceTester $I): void
    {
        // WebDriver warmup — retry initial page load to survive Selenium startup lag
        $lastException = null;

        for ($attempt = 1; $attempt <= self::WEBDRIVER_WARMUP_ATTEMPTS; ++$attempt) {
            try {
                $I->amOnPage('/robots.txt');
                $lastException = null;

                break;
            } catch (Exception $e) {
                $lastException = $e;
                sleep($attempt); // 1s, 2s, 3s
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        // The browser session is shared between scenarios, so a test that narrows the
        // window for a responsive check would otherwise leave every later scenario on
        // a phone-sized viewport with a collapsed navigation.
        $I->resizeWindow(self::DEFAULT_WINDOW_WIDTH, self::DEFAULT_WINDOW_HEIGHT);

        $I->setCookie('SELENIUM', 'SELENIUM', [
            'domain' => '.moje-hospodareni.cz',
            'secure' => false,
            'httpOnly' => false,
        ], true);
        $I->amOnPage('/');
    }

    protected function openLinkAndWaitForElementWithSkautisRetry(
        AcceptanceTester $I,
        string $linkSelector,
        string $expectedSelector,
        ?string $expectedUrlPart = null,
    ): void {
        $linkUrl = null;

        $this->runWithSkautisRetry(
            $I,
            static function () use ($I, $linkSelector, $expectedSelector, &$linkUrl): string {
                // The link is only clickable on the first attempt; a retry re-opens its target
                // directly, because the SkautIS error page no longer contains the link.
                if ($linkUrl === null) {
                    $I->waitForElementVisible($linkSelector, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
                    $linkUrl = (string) $I->grabAttributeFrom($linkSelector, 'href');
                    $I->clickStable($linkSelector);
                } else {
                    $I->amOnPage($linkUrl);
                }

                return $I->waitForElementOrSkautisConnectionError($expectedSelector);
            },
            'opening '.$linkSelector,
            $expectedSelector,
        );

        if ($expectedUrlPart === null) {
            return;
        }

        $I->seeInCurrentUrl($expectedUrlPart);
    }

    /**
     * Every authenticated page load calls SkautIS during presenter startup, so a direct
     * navigation can render a transient WSDL error page instead of the expected screen.
     */
    protected function openPageAndWaitForElementWithSkautisRetry(
        AcceptanceTester $I,
        string $url,
        string $expectedSelector,
    ): void {
        $this->runWithSkautisRetry(
            $I,
            static function () use ($I, $url, $expectedSelector): string {
                $I->amOnPage($url);

                return $I->waitForElementOrSkautisConnectionError($expectedSelector);
            },
            'opening '.$url,
            $expectedSelector,
        );
    }

    /**
     * @param callable(): string $attempt
     */
    protected function runWithSkautisRetry(
        AcceptanceTester $I,
        callable $attempt,
        string $actionDescription,
        string $expectedDescription,
    ): void {
        $pageState = AcceptanceTester::PAGE_STATE_UNKNOWN;

        for ($attemptNumber = 1; $attemptNumber <= self::SKAUTIS_PAGE_OPEN_ATTEMPTS; ++$attemptNumber) {
            try {
                $pageState = $attempt();
            } catch (SkautisWsdlPageException) {
                $pageState = AcceptanceTester::PAGE_STATE_SKAUTIS_UNAVAILABLE;
            }

            if ($pageState !== AcceptanceTester::PAGE_STATE_SKAUTIS_UNAVAILABLE || $attemptNumber === self::SKAUTIS_PAGE_OPEN_ATTEMPTS) {
                break;
            }

            sleep(self::SKAUTIS_PAGE_OPEN_RETRY_DELAY_SECONDS);
        }

        if ($pageState === AcceptanceTester::PAGE_STATE_SKAUTIS_UNAVAILABLE) {
            $I->failBecauseSkautisConnectionFailedAfterRetries(
                $actionDescription,
                $expectedDescription,
                self::SKAUTIS_PAGE_OPEN_ATTEMPTS,
            );
        }

        if ($pageState !== AcceptanceTester::PAGE_STATE_EXPECTED) {
            Assert::fail(
                'Expected '.$expectedDescription.' while '.$actionDescription
                .', got '.$pageState.' state at '.$I->grabFromCurrentUrl().'.',
            );
        }
    }
}
