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
        $I->clickLinkAndWaitForElementWithSkautisRetry(
            $linkSelector,
            $expectedSelector,
            $expectedUrlPart,
            self::SKAUTIS_PAGE_OPEN_ATTEMPTS,
            self::SKAUTIS_PAGE_OPEN_RETRY_DELAY_SECONDS,
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
