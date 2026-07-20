<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Exception;

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
}
