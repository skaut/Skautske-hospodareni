<?php

declare(strict_types=1);

namespace Page;

use function date;
use function sprintf;

class Payment
{
    /** @var \AcceptanceTester */
    private $tester;

    public function __construct(\AcceptanceTester $tester)
    {
        $this->tester = $tester;
    }

    public function fillName(string $name) : void
    {
        $this->tester->fillField('Název', $name);
    }

    public function fillEmail(string $name) : void
    {
        $this->tester->fillField('Email', $name);
    }

    public function fillAmount($amount) : void
    {
        $this->tester->fillField('Částka', $amount);
    }

    public function addPayment(string $name, ?string $email, float $amount) : void
    {
        $this->tester->click('//button[contains(.,\'Přidat platbu\')]');
        $this->tester->click('… obecnou');

        $this->tester->waitForElementVisible('.modal-dialog');
        $this->fillName($name);

        if ($email !== null) {
            $this->fillEmail($email);
        }

        $this->fillAmount($amount);
        $this->selectNextWorkdayForDueDate();
        $this->submitPayment();
    }

    public function seeNumberOfPaymentsWithState(string $state, int $count) : void
    {
        $this->tester->seeNumberOfElements("(//*[text()='$state'])", $count);
    }

    public function selectNextWorkdayForDueDate() : void
    {
        $I = $this->tester;

        $dayOfWeek = date('N');

        $daysToNextWorkday = $dayOfWeek < 5 ? 1 : 8 - $dayOfWeek;

        $date = (new \DateTime())->modify(sprintf('+ %d days', $daysToNextWorkday))->format('d.m. Y');

        $I->fillField('Splatnost', $date);
        $I->click('.modal-dialog'); // Close date picker
    }

    public function submitPayment() : void
    {
        $this->tester->click('Přidat platbu', '.modal-footer');
        $this->tester->waitForElementNotVisible('.modal-dialog');
        $this->tester->wait(3);
        $this->tester->waitForElementClickable('(//button[contains(.,\'Přidat platbu\')])');
    }
}
