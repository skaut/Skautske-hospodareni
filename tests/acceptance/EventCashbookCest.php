<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use Facebook\WebDriver\WebDriverKeys;
use Model\Cashbook\Operation;

use function date;
use function sprintf;
use function time;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class EventCashbookCest extends BaseAcceptanceCest
{
    private const BALANCE_SELECTOR = '.ui--balance';
    private const NO_CHITS_MESSAGE = 'žádné doklady';

    protected AcceptanceTester $I;
    private string $eventName;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->eventName = 'Acceptance test event ' . time();
        $this->I         = $I;

        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group cashbook */
    public function createEventCashbook(): void
    {
        $this->createEvent();
        $this->goToCashbookPage();
        $this->createExpenseChit();
        $this->editExpenseChit();
        $this->addIncomeChit();
        $this->removeBothChits();
        $this->cancelEvent();
    }

    private function createEvent(): void
    {
        $I = $this->I;
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
        $I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
        $I->waitForText($this->eventName);
        $I->click($this->eventName);
    }

    private function goToCashbookPage(): void
    {
        $I = $this->I;
        $I->amGoingTo('open cashbook');

        $cashbookButton = 'Evidence plateb';
        $I->waitForText($cashbookButton);
        $I->click($cashbookButton);

        $I->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function createExpenseChit(): void
    {
        $I = $this->I;
        $I->click('Nový doklad');
        $I->amGoingTo('create expense chit');

        $purpose = 'Nákup chleba';

        $this->fillChitForm(new ChronosDate(), $purpose, Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '100 + 1');
        $I->scrollTo('input[name="send"]');
        $I->click('input[name="send"]');
        $this->waitForBalance('-101,00');
    }

    private function editExpenseChit(): void
    {
        $I = $this->I;
        $I->wantTo('Update expense chit amount');
        $I->scrollTo('h4[id="chitList-payment"]');
        $I->click('.ui--editChit');
        $I->waitForElement('[name="pid"]:not([value=""])');

        $I->fillField('items[0][price]', '121');
        $I->scrollTo('input[name="send"]');
        $I->click('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function addIncomeChit(): void
    {
        $I = $this->I;
        $I->click('Nový doklad');
        $I->amGoingTo('add income chit');

        $this->fillChitForm(new ChronosDate(), 'Účastnické poplatky', Operation::INCOME(), 'Přijmy od účastníků', 'Testovací skaut 2', '100');
        $I->scrollTo('input[name="send"]');
        $I->click('input[name="send"]');

        $this->waitForBalance('-21,00');
    }

    private function removeBothChits(): void
    {
        $I = $this->I;
        $I->amGoingTo('remove both chits');

        $I->scrollTo('h4[id="chitList-payment"]');
        $this->removeChit(1);
        $this->waitForBalance('-121,00');
        $I->scrollTo('h4[id="chitList-payment"]');
        $this->removeChit(1);
        $I->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function cancelEvent(): void
    {
        $I = $this->I;
        $I->amGoingTo('cancel the event');

        $I->click('Akce');

        $cancelButton = sprintf("//a[text()='%s']/ancestor::tr//a[contains(@class, 'btn-danger')][1]", $this->eventName);

        $I->waitForElement($cancelButton);
        $I->disablePopups();
        $I->click($cancelButton);

        $I->waitForElementNotVisible($cancelButton);
    }

    private function fillChitForm(ChronosDate $date, string $purpose, Operation $type, string $category, string $recipient, string $amount): void
    {
        $this->I->wait(2); // unroll block
        $this->I->wantToTest('Uložit');
        $this->I->fillField('Datum', $date->format('d.m. Y'));
        $this->I->pressKey('body', [WebDriverKeys::ESCAPE]); // close datepicker
        $this->I->fillField('Účel', $purpose);
        $this->I->selectOption('#chit-type', $type->equals(Operation::EXPENSE()) ? 'Výdaje' : 'Příjmy');
        $this->I->selectOption(sprintf('items[0][%sCategories]', $type->equals(Operation::EXPENSE()) ? 'expense' : 'income'), $category);
        $this->I->fillField('Komu/Od', $recipient);
        $this->I->fillField('items[0][price]', $amount);
    }

    private function waitForBalance(string $balance): void
    {
        $this->I->expectTo(sprintf('see %s CZK as final balance', $balance));
        $this->I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
        $this->I->wait(2);
        $this->I->waitForText($balance, 10, self::BALANCE_SELECTOR);
    }

    private function removeChit(int $position): void
    {
        $this->I->disablePopups();
        $this->I->click('.ui--removeChit');
    }
}
