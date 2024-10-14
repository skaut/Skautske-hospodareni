<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
abstract class BaseAcceptanceCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->setCookie('SELENIUM', 'SELENIUM', [
            'domain' => '.moje-hospodareni.cz',
            'secure' => false,
            'httpOnly' => false,
        ], true);
        $I->executeJS('document.documentElement.style.scrollBehavior = "auto !important";');
    }
}
