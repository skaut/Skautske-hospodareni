<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('create payment group');
$I->login();
$I->click('Platby');
$I->waitForText('Přehled plateb');
$I->click('Založit skupinu plateb');
$I->waitForText('Obecná');
$I->click('Obecná');
$I->fillField('Název', 'Jaráky');
$I->click('Založit skupinu');

$I->see('Zatím zde nejsou žádné platby.');

$page = new \Page\Payment($I);

$I->amGoingTo('add first payment');

$page->fillName('Testovací platba');
$page->fillAmount(1000);
$page->selectNextWorkdayForDueDate();
$page->submitPayment();

$I->waitForText('Platba byla přidána');

$I->amGoingTo('add another payment');
$page->fillName('Testovací platba 2');
$page->fillAmount(500);
$page->selectNextWorkdayForDueDate();
$page->submitPayment();

$I->amGoingTo('add third payment');
$page->fillName('Testovací platba 2');
$page->fillAmount(300);
$page->selectNextWorkdayForDueDate();
$page->submitPayment();

$I->amGoingTo('mark second payment as complete');
$I->click('(//*[@title="Zaplaceno"])[2]');

$I->canSeeNumberOfElements('(//*[text()="Připravena"])', 2);
$I->see('Dokončena');

$I->amGoingTo('close and reopen group');
$I->click('Uzavřít');
$I->waitForText('Znovu otevřít');
$I->click('Znovu otevřít');
$I->waitForText('Uzavřít');
