<?php

declare(strict_types=1);


namespace acceptance;

use AcceptanceTester;

abstract class AbstractBaseAcceptanceCest
{

    public function _before(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->setCookie('SELENIUM', 'SELENIUM', [
            'domain' => '.moje-hospodareni.cz',
            'secure' => false,
            'httpOnly' => false
        ], true);
    }
}