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

    protected AcceptanceTester $tester;

    private string $eventName;

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
        $i = $this->tester;
        $i->amGoingTo('create event');

        $i->click('Založit novou akci');
        $i->waitForText('Název akce');

        $today = date('d.m. Y');

        $i->fillField('Název akce', $this->eventName);
        $i->fillField('Od', $today);
        $i->click('//body'); // close datepicker

        $i->fillField('Do', $today);

        $i->click('.ui--createEvent');
        $i->see('Základní údaje');

        // Go through datagrid
        $i->click('Akce');
        $i->click($this->eventName);
    }

    private function goToCashbookPage() : void
    {
        $i = $this->tester;
        $i->amGoingTo('open cashbook');

        $cashbookButton = 'Evidence plateb';
        $i->waitForText($cashbookButton);
        $i->click($cashbookButton);

        $i->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function createExpenseChit() : void
    {
        $i = $this->tester;
        $i->amGoingTo('create expense chit');

        $purpose = 'Nákup chleba';

        $this->fillChitForm(new Date(), $purpose, Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '100 + 1');
        $i->click('Uložit');

        $this->waitForBalance('-101,00');
    }

    private function editExpenseChit() : void
    {
        $i = $this->tester;
        $i->wantTo('Update expense chit amount');

        $i->click('.ui--editChit');
        $i->waitForElement('[name="pid"]:not([value=""])');

        $i->fillField('items[0][price]', '121');
        $i->click('Uložit');

        $this->waitForBalance('-121,00');
    }

    private function addIncomeChit() : void
    {
        $i = $this->tester;
        $i->amGoingTo('add income chit');

        $this->fillChitForm(new Date(), 'Účastnické poplatky', Operation::INCOME(), 'Přijmy od účastníků', 'Testovací skaut 2', '100');
        $i->click('Uložit');

        $this->waitForBalance('-21,00');
    }

    private function removeBothChits() : void
    {
        $i = $this->tester;
        $i->amGoingTo('remove both chits');

        $this->removeChit(1);
        $this->waitForBalance('-121,00');

        $this->removeChit(1);
        $i->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function cancelEvent() : void
    {
        $i = $this->tester;
        $i->amGoingTo('cancel the event');

        $i->click('Akce');

        $cancelButton = sprintf("//a[text()='%s']/ancestor::tr//a[contains(@class, 'btn-danger')][1]", $this->eventName);

        $i->waitForElement($cancelButton);
        $i->disablePopups();
        $i->click($cancelButton);

        $i->waitForElementNotVisible($cancelButton);
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
