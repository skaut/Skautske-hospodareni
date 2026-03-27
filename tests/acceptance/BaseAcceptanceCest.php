<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Exception;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
abstract class BaseAcceptanceCest
{
    public function _before(AcceptanceTester $I): void
    {
        // WebDriver warmup — retry initial page load to survive Selenium startup lag
        $lastException = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $I->amOnPage('/');
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
    }
}

