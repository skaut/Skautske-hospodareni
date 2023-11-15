<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;

use function date;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class CreateCashbookCest extends BaseAcceptanceCest
{
    /**
     * @desc Create cashbooks for current and next year
     * @group cashbooks
     */
    public function testCreateCashbooks(AcceptanceTester $I): void
    {
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);

        $I->click('Jednotka');
        $I->amGoingTo('Create first unit cashbook - for current year');
        $this->createCashbook($I, (int) date('Y'));
        $I->amGoingTo('Create cashbook for different year');
        $I->click('#unit-cashbook-menu');
        $createCashbookButton = 'Přidat pokladní knihu';
        $I->waitForText($createCashbookButton);
        $I->click($createCashbookButton);
        $this->fillModalAndSubmit($I, (int) date('Y') + 1);
    }

    private function createCashbook(AcceptanceTester $I, int $year): void
    {
        $I->click('Založit novou pokladní knihu');
        $this->fillModalAndSubmit($I, $year);
    }

    private function fillModalAndSubmit(AcceptanceTester $I, int $year): void
    {
        $I->waitForText('Vyberte rok');
        $I->selectOption('Rok', $year);
        $I->click('Založit', '.modal-footer');

        $I->see('Pokladní kniha byla vytvořena');
        $I->see((string) $year);
    }
}
