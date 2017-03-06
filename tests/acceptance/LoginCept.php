<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('login to hskauting');
$I->amOnPage('/');
$I->click('Přihlásit se');
$I->see('přihlášení');
$I->fillField('Uživatelské jméno:', 'stredisko.koprivnice');
$I->fillField('Heslo:', 'koprivnice.Web5');
$I->click('Přihlásit');
$I->waitForText('Seznam akcí');
$I->amOnPage('/role');
