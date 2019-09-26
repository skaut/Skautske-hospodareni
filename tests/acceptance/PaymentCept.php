<?php

declare(strict_types=1);

use Page\Payment;

$I = new AcceptanceTester($scenario);

$I->wantTo('create payment group');

$I->resetEmails();
$I->haveInDatabase('pa_smtp', [
    'unitId' => 27266,
    'host' => 'smtp-hospodareni.loc',
    'secure' => '',
    'username' => 'test@hospodareni.loc',
    'password' => '',
    'sender' => 'test@hospodareni.loc',
    'created' => '2017-06-15 00:00:00',
]);

$I->haveInDatabase('pa_bank_account', [
    'name' => 'Acceptance',
    'unit_id' => 27266,
    'token' => null,
    'created_at' => '2017-08-24 00:00:00',
    'allowed_for_subunits' => 1,
    'number_prefix' => null,
    'number_number' => '2000942144',
    'number_bank_code' => '2010',
]);

$I->login($I::UNIT_LEADER_ROLE);
$I->click('Platby');
$I->waitForText('Platební skupiny');
$I->click('Založit skupinu plateb');
$I->waitForText('Obecná');
$I->click('Obecná');
$I->fillField('Název', 'Jaráky');
$I->click('//option[text()="Vyberte email"]');
$I->click('//option[text()="test@hospodareni.loc"]');

$I->click('//option[text()="Vyberte bankovní účet"]');
$I->click('//option[text()="Acceptance"]');
$I->click('Založit skupinu');

$I->see('Skupina byla založena');

$page = new Payment($I);

$I->wantTo('create payments');

$I->amGoingTo('add first payment');
$page->addPayment('Testovací platba 1', null, 500);

$I->amGoingTo('add second payment');
$page->addPayment('Testovací platba 2', null, 500);

$I->amGoingTo('add third payment');
$page->addPayment('Testovací platba 3', 'frantisekmasa1@gmail.com', 300);

$I->wantTo('complete payment');

$I->amGoingTo('mark second payment as complete');
$I->click('(//*[@title="Zaplaceno"])[2]');

$I->canSeeNumberOfElements('(//*[text()="Nezaplacena"])', 2);
$I->see('Dokončena');

$I->wantTo('send payment email');

$I->amGoingTo('send third payment');
$I->click('//a[contains(@class, \'ui--sendEmail\')]');
$I->waitForText('Odeslána');

$page->seeNumberOfPaymentsWithState('Nezaplacena', 1);
$page->seeNumberOfPaymentsWithState('Odeslána', 1);
$page->seeNumberOfPaymentsWithState('Dokončena', 1);

$I->seeEmailCount(1);

$I->wantTo('close and reopen group');
$I->click('Uzavřít');
$I->waitForText('Znovu otevřít');
$I->click('Znovu otevřít');
$I->waitForText('Uzavřít');


$I->amGoingTo('close group');
$I->click('Uzavřít');
