<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Model\Cashbook\Operation;
use WebDriverKeys;
use function date;
use function sprintf;
use function time;

class CashbookTest extends Unit
{
    private const BALANCE_SELECTOR = '.ui--balance';
    private const NO_CHITS_MESSAGE = 'žádné doklady';

    /** @var AcceptanceTester */
    protected $tester;

    /** @var string */
    private $eventName;

    protected function _before() : void
    {
        $this->eventName = 'Acceptance test event ' . time();
    }

    public function test() : void
    {
        $this->tester->login($this->tester::UNIT_LEADER_ROLE);

        $this->createEvent();
        $this->goToCashbookPage();
        $this->createExpenseChit();
        $this->editExpenseChit();
        $this->addIncomeChit();
        $this->removeBothChits();
        $this->cancelEvent();
    }

    private function createEvent() : void
    {
        $I = $this->tester;
        $I->amGoingTo('create event');

        $I->click('Založit novou akci');
        $I->waitForText('Název akce');

        $today = date('d.m. Y');

        $I->fillField('Název akce', $this->eventName);
        $I->fillField('Od', $today);
        $I->click('//body'); // close datepicker

        $I->fillField('Do', $today);

        $I->click('.ui--createEvent');
        $I->see('Základní údaje');

        // Go through datagrid
        $I->click('Akce');
        $I->click($this->eventName);
    }

    private function goToCashbookPage() : void
    {
        $I = $this->tester;
        $I->amGoingTo('open cashbook');

        $cashbookButton = 'Evidence plateb';
        $I->waitForText($cashbookButton);
        $I->click($cashbookButton);

        $I->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function createExpenseChit() : void
    {
        $I = $this->tester;
        $I->amGoingTo('create expense chit');

        $purpose = 'Nákup chleba';

        $this->fillChitForm(new Date(), $purpose, Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '100 + 1');
        $I->click('Uložit');

        $this->waitForBalance('-101,00');
    }

    private function editExpenseChit() : void
    {
        $I = $this->tester;
        $I->wantTo('Update expense chit amount');

        $I->click('.ui--editChit');
        $I->waitForElement('[name="pid"]:not([value=""])');

        $I->fillField('items[0][price]', '121');
        $I->click('Uložit');

        $this->waitForBalance('-121,00');
    }

    private function addIncomeChit() : void
    {
        $I = $this->tester;
        $I->amGoingTo('add income chit');

        $this->fillChitForm(new Date(), 'Účastnické poplatky', Operation::INCOME(), 'Přijmy od účastníků', 'Testovací skaut 2', '100');
        $I->click('Uložit');

        $this->waitForBalance('-21,00');
    }

    private function removeBothChits() : void
    {
        $I = $this->tester;
        $I->amGoingTo('remove both chits');

        $this->removeChit(1);
        $this->waitForBalance('-121,00');

        $this->removeChit(1);
        $I->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function cancelEvent() : void
    {
        $I = $this->tester;
        $I->amGoingTo('cancel the event');

        $I->click('Akce');

        $cancelButton = sprintf("//a[text()='%s']/ancestor::tr//a[contains(@class, 'btn-danger')]", $this->eventName);

        $I->waitForElement($cancelButton);
        $I->disablePopups();
        $I->click($cancelButton);

        $I->waitForElementNotVisible($cancelButton);
    }

    private function fillChitForm(Date $date, string $purpose, Operation $type, string $category, string $recipient, string $amount) : void
    {
        $this->tester->fillField('Datum', $date->format('d.m. Y'));
        $this->tester->pressKey('body', WebDriverKeys::ESCAPE); // close datepicker
        $this->tester->fillField('Účel', $purpose);
        $this->tester->selectOption('#chit-type', $type->equals(Operation::EXPENSE()) ? 'Výdaje' : 'Příjmy');
        $this->tester->selectOption(sprintf('items[0][%sCategories]', $type->equals(Operation::EXPENSE()) ? 'expense' : 'income'), $category);
        $this->tester->fillField('Komu/Od', $recipient);
        $this->tester->fillField('items[0][price]', $amount);
    }

    private function waitForBalance(string $balance) : void
    {
        $this->tester->expectTo(sprintf('see %s CZK as final balance', $balance));
        $this->tester->waitForText($balance, null, self::BALANCE_SELECTOR);
    }

    private function removeChit(int $position) : void
    {
        $this->tester->disablePopups();
        $this->tester->click('.ui--removeChit');
    }
}
