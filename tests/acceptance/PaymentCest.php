<?php

declare(strict_types=1);

namespace acceptance;

use Page\Payment;
use AcceptanceTester;


class PaymentCest extends AbstractBaseAcceptanceCest
{
    protected AcceptanceTester $I;
    protected Payment $page;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        $this->I = $I;
        $this->page = new Payment($I);
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }


    /**
     * @group payment
     */
    public function createPaymentGroup(): void
    {
        $I = $this->I;
        $page = $this->page;

        $I->wantTo('create payment group');

        $I->click('Platby');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Obecná');
        $I->click('Obecná');
        $I->fillField('Název', 'Jaráky');
        $I->click('//option[text()="Vyberte e-mail"]');
        $I->click('//option[text()="test@hospodareni.loc"]');

        $I->click('//option[text()="Vyberte bankovní účet"]');
        $I->click('//option[text()="Acceptance"]');
        $I->scrollTo('input[name="send"]');
        $I->click('Založit skupinu');

        $I->see('Skupina byla založena');


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

        $page->seeNumberOfPaymentsWithState('Nezaplacena', 2);
        $page->seeNumberOfPaymentsWithState('Dokončena', 1);

        $I->wantTo('close and reopen group');
        $I->click('Uzavřít');
        $I->waitForText('Znovu otevřít');
        $I->click('Znovu otevřít');
        $I->waitForText('Uzavřít');

        $I->amGoingTo('close group');
        $I->click('Uzavřít');
    }
}
