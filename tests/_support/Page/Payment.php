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

    public function selectTomorrowForDueDate()
    {
        $I = $this->tester;
        $I->click('(//table//input)[6]');
        $tomorrow = "//td[text()='" . (date('j') + 1) . "']"; // Tlačítko v datepickeru
        $I->waitForElementVisible($tomorrow);
        $I->click($tomorrow);
    }

    public function submitPayment()
    {
        $this->tester->click('Přidat platbu');
    }

}
