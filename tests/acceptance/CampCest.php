<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class CampCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    public function createListCamp(): void
    {
        $I = $this->I;
        $I->wantTo('List camps');
        $I->click('Tábory');
        $I->waitForText('Tábory', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->selectOption('Rok', 2024);
    }
}
