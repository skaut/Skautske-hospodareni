<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use function date;

$I = new AcceptanceTester($scenario);

$I->login(AcceptanceTester::UNIT_LEADER_ROLE);

function fillModalAndSubmit(AcceptanceTester $I, int $year) : void
{
    $I->waitForText('Vyberte rok');
    $I->selectOption('Rok', $year);
    $I->click('Založit', '.modal-footer');

    $I->see('Pokladní kniha byla vytvořena');
    $I->see($year);
}

$I->click('Jednotka');

$I->amGoingTo('Create first unit cashbook - for current year');

$I->click('Založit novou pokladní knihu');

fillModalAndSubmit($I, (int) date('Y'));

$I->amGoingTo('Create cashbook for different year');
$I->click('#unit-cashbook-menu');

$createCashbookButton = 'Přidat pokladní knihu';
$I->waitForText($createCashbookButton);
$I->click($createCashbookButton);

fillModalAndSubmit($I, (int) date('Y') + 1);
