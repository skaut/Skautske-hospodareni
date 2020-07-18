<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use function date;

$i = new AcceptanceTester($scenario);

$i->login(AcceptanceTester::UNIT_LEADER_ROLE);

function fillModalAndSubmit(AcceptanceTester $i, int $year) : void
{
    $i->waitForText('Vyberte rok');
    $i->selectOption('Rok', $year);
    $i->click('Založit', '.modal-footer');

    $i->see('Pokladní kniha byla vytvořena');
    $i->see($year);
}

$i->click('Jednotka');

$i->amGoingTo('Create first unit cashbook - for current year');

$i->click('Založit novou pokladní knihu');

fillModalAndSubmit($i, (int) date('Y'));

$i->amGoingTo('Create cashbook for different year');
$i->click('#unit-cashbook-menu');

$createCashbookButton = 'Přidat pokladní knihu';
$i->waitForText($createCashbookButton);
$i->click($createCashbookButton);

fillModalAndSubmit($i, (int) date('Y') + 1);
