<?php

declare(strict_types=1);

use Cake\Chronos\Date;

$I = new AcceptanceTester($scenario);

$I->login($I::UNIT_LEADER_ROLE);

/**
 * Create new Event
 */
$I->click('Založit novou akci');
$I->waitForText('Název akce');

$I->amGoingTo('create event');
$eventName = 'Acceptance test event ' . time();
$today = date('d.m. Y');

$I->fillField('Název akce', $eventName);
$I->fillField('Od', $today);
$I->fillField('Do', $today);

$I->click('.ui--createEvent');

/**
 * Go to cashbook page
 */
$cashbookButton = 'Pokladní kniha';
$I->waitForText($cashbookButton);
$I->click($cashbookButton);

/**
 * Test interaction with cashbook
 */
$noChitsMessage = 'žádné doklady';

$I->waitForText($noChitsMessage);

$page = new \Page\Cashbook($I);

$I->amGoingTo('create chit');

$purpose = 'Nákup chleba';

$page->fillChitForm(new Date(), $purpose, 'Výdaje', 'Potraviny', 'Testovací skaut', '100 + 1');
$I->click('Uložit');

$I->waitForElementNotVisible($noChitsMessage);

$page->seeBalance('-101,00');

$I->click('.ui--editChit');
$I->waitForElement('[name="pid"]:not([value=""])');


// change amount
$page->fillChitForm(new Date(), $purpose, 'Výdaje', 'Potraviny', 'Testovací skaut', '120 + 1');
$I->click('Uložit');

$I->waitForText('121,00');

$page->seeBalance('-121,00');

// Remove chit
$I->disablePopups();
$I->click('.ui--removeChit');
$I->waitForText($noChitsMessage);

/**
 * Cancel test Event
 */
$I->click('Akce');

$cancelButton = sprintf('[data-cancel-event="%s"]', $eventName);

$I->waitForElement($cancelButton);
$I->disablePopups();
$I->click($cancelButton);

$I->waitForElementNotVisible($cancelButton);
