<?php

namespace Page;

class Payment
{

    /** @var \AcceptanceTester */
    private $tester;

    public function __construct(\AcceptanceTester $tester)
    {
        $this->tester = $tester;
    }

    public function fillName(string $name)
    {
        $this->tester->fillField("(//table//input)[1]", $name);
    }

    public function fillAmount($amount)
    {
        $this->tester->fillField("(//table//input)[3]", $amount);
    }

    public function selectNextWorkdayForDueDate()
    {
        $I = $this->tester;
        $I->click('(//table//input)[6]');

        $dayOfWeek = date('N');

        $daysToNextWorkday = $dayOfWeek < 5 ? 1 : 8 - $dayOfWeek;

		$date = (new \DateTime())->modify("+ $daysToNextWorkday days")->format('j');

        $button = "(//td[text()='$date'])[last()]"; // Tlačítko v datepickeru
        $I->waitForElementVisible($button);
        $I->click($button);
        $I->waitForElementNotVisible($button);
    }

    public function submitPayment()
    {
        $this->tester->click('Přidat platbu');
    }

}
