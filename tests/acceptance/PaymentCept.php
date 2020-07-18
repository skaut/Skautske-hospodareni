<?php

declare(strict_types=1);

use Page\Payment;

$i = new AcceptanceTester($scenario);

$i->wantTo('create payment group');

$i->resetEmails();
$i->haveInDatabase('pa_smtp', [
    'unitId' => 27266,
    'host' => 'smtp-hospodareni.loc',
    'secure' => '',
    'username' => 'test@hospodareni.loc',
    'password' => '',
    'sender' => 'test@hospodareni.loc',
    'created' => '2017-06-15 00:00:00',
]);

$i->haveInDatabase('pa_bank_account', [
    'name' => 'Acceptance',
    'unit_id' => 27266,
    'token' => null,
    'created_at' => '2017-08-24 00:00:00',
    'allowed_for_subunits' => 1,
    'number_prefix' => null,
    'number_number' => '2000942144',
    'number_bank_code' => '2010',
]);

$i->login($i::UNIT_LEADER_ROLE);
$i->click('Platby');
$i->waitForText('Platební skupiny');
$i->click('Založit skupinu plateb');
$i->waitForText('Obecná');
$i->click('Obecná');
$i->fillField('Název', 'Jaráky');
$i->click('//option[text()="Vyberte email"]');
$i->click('//option[text()="test@hospodareni.loc"]');

$i->click('//option[text()="Vyberte bankovní účet"]');
$i->click('//option[text()="Acceptance"]');
$i->click('Založit skupinu');

$i->see('Skupina byla založena');

$page = new Payment($i);

$i->wantTo('create payments');

$i->amGoingTo('add first payment');
$page->addPayment('Testovací platba 1', null, 500);

$i->amGoingTo('add second payment');
$page->addPayment('Testovací platba 2', null, 500);

$i->amGoingTo('add third payment');
$page->addPayment('Testovací platba 3', 'frantisekmasa1@gmail.com', 300);

$i->wantTo('complete payment');

$i->amGoingTo('mark second payment as complete');
$i->click('(//*[@title="Zaplaceno"])[2]');

$i->canSeeNumberOfElements('(//*[text()="Nezaplacena"])', 2);
$i->see('Dokončena');

$i->wantTo('send payment email');

$i->amGoingTo('send third payment');
$i->click('//a[contains(@class, \'ui--sendEmail\')]');
$i->waitForElement('//*[@title="Odeslané emaily"]');

$page->seeNumberOfPaymentsWithState('Nezaplacena', 2);
$page->seeNumberOfPaymentsWithState('Dokončena', 1);

$i->seeEmailCount(1);

$i->wantTo('close and reopen group');
$i->click('Uzavřít');
$i->waitForText('Znovu otevřít');
$i->click('Znovu otevřít');
$i->waitForText('Uzavřít');


$i->amGoingTo('close group');
$i->click('Uzavřít');
