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
        $this->createTwoLineExpenseChit();
        $this->removeChits();
        $this->checkRemoveSecondLineExpenseChit();
        $this->cancelEvent();
    }

    private function createEvent(): void
    {
        $this->I->amGoingTo('create event');

        $this->I->click('Založit novou akci');
        $this->I->waitForText('Název akce');

        $today = date('d.m. Y');

        $this->I->fillField('Název akce', $this->eventName);
        $this->I->fillField('Od', $today);
        $this->I->click('//body'); // close datepicker

        $this->I->fillField('Do', $today);

        $this->I->click('.ui--createEvent');
        $this->I->see('Základní údaje');

        // Go through datagrid
        $this->I->click('Akce');
        $this->I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
        $this->I->waitForText($this->eventName);
        $this->I->wait(4);
        $this->I->click($this->eventName);
    }

    private function goToCashbookPage(): void
    {
        $this->I->amGoingTo('open cashbook');

        $cashbookButton = 'Evidence plateb';
        $this->I->waitForText($cashbookButton);
        $this->I->click($cashbookButton);

        $this->I->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function createExpenseChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('create expense chit');

        $purpose = 'Nákup chleba';

        $this->fillChitForm(new ChronosDate(), $purpose, Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '100 + 1');
        $this->I->scrollTo('input[name="send"]');
        $this->I->click('input[name="send"]');
        $this->waitForBalance('-101,00');
    }

    private function editExpenseChit(): void
    {
        $this->I->wantTo('Update expense chit amount');
        $this->I->scrollTo('h4[id="chitList-payment"]');
        $this->I->click('.ui--editChit');
        $this->I->waitForElement('[name="pid"]:not([value=""])');

        $this->I->fillField('items[0][price]', '121');
        $this->I->scrollTo('input[name="send"]');
        $this->I->wait(2);
        $this->I->click('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function addIncomeChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('add income chit');

        $this->fillChitForm(new ChronosDate(), 'Účastnické poplatky', Operation::INCOME(), 'Přijmy od účastníků', 'Testovací skaut 2', '100');
        $this->I->scrollTo('input[name="send"]');
        $this->I->wait(2);
        $this->I->click('input[name="send"]');

        $this->waitForBalance('-21,00');
    }

    private function createTwoLineExpenseChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('create expense chit');
        $this->fillChitForm(new ChronosDate(), 'Rohlíky', Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '50');
        $this->I->click('input[name="items[addItem]"]');
        $this->I->wait(2);
        $this->I->expect('Odebrat položku');
        $this->I->seeElement('input[name="items[1][remove]"]');
        $this->fillSecondChitForm('Hřebíky', Operation::EXPENSE(), 'Materiál', 'Testovací skaut', '50');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]');
        $this->I->wait(2);
        $this->I->click('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function checkRemoveSecondLineExpenseChit(): void
    {
        $this->goToCashbookPage();

        $this->I->click('Nový doklad');
        $this->I->waitForText('Položky');
        $this->I->click('input[name="items[addItem]"]');
        $this->I->expect('Odebrat položku');
        $this->I->seeElement('input[name="items[0][remove]"]');
        $this->I->click('input[name="items[0][remove]"]');
        $this->I->wait(6); //waiting for animation and ajax
        $this->I->dontSeeElement('//input[@type="submit" and @value="Odebrat položku"]');
        $this->I->click('Nový doklad');
        $this->I->wait(6); //waiting for animation and ajax
        $this->I->dontSeeElement('//input[@type="submit" and @value="Uložit"]');
    }

    private function removeChits(): void
    {
        $this->I->amGoingTo('remove chits');
        $this->I->scrollTo('h4[id="chitList-payment"]');
        $this->removeChit(1);
        $this->waitForBalance('-221,00');
        $this->I->scrollTo('h4[id="chitList-payment"]');
        $this->removeChit(1);
        $this->waitForBalance('-100,00');
        $this->I->scrollTo('h4[id="chitList-payment"]');
        $this->removeChit(1);
        $this->I->waitForText(self::NO_CHITS_MESSAGE);
    }

    private function cancelEvent(): void
    {
        $this->I->amGoingTo('cancel the event');

        $this->I->click('Akce');

        $cancelButton = sprintf("//a[text()='%s']/ancestor::tr//a[contains(@class, 'btn-danger')][1]", $this->eventName);

        $this->I->waitForElement($cancelButton);
        $this->I->disablePopups();
        $this->I->wait(2);
        $this->I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
        $this->I->click($cancelButton);

        $this->I->waitForElementNotVisible($cancelButton);
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

    private function fillSecondChitForm(string $purpose, Operation $type, string $category, string $recipient, string $amount): void
    {
        $this->I->wantToTest('Uložit');
        $this->I->fillField('items[1][purpose]', $purpose);
        $this->I->selectOption(sprintf('items[1][%sCategories]', $type->equals(Operation::EXPENSE()) ? 'expense' : 'income'), $category);
        $this->I->fillField('Komu/Od', $recipient);
        $this->I->fillField('items[1][price]', $amount);
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
