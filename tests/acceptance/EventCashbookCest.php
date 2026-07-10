<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use App\Model\Cashbook\Operation;
use Cake\Chronos\ChronosDate;
use Facebook\WebDriver\WebDriverKeys;
use Throwable;

use function count;
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
    private bool $eventCreated = false;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->eventName = 'Acceptance test event '.time();
        $this->eventCreated = false;
        $this->I = $I;

        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    public function _after(AcceptanceTester $I): void
    {
        if (! $this->eventCreated) {
            return;
        }

        $this->cleanupEvent($I);
    }

    /** @group cashbook */
    public function createEventCashbook(): void
    {
        $this->I->click('Akce');
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
        $this->eventCreated = true;
        $this->I->waitForElement('[data-test="event-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->waitForText('Základní údaje', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, 'nav[aria-label="Navigace akce"]');
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
        $this->I->clickStable('input[name="send"]');
        $this->waitForBalance('-101,00');
    }

    private function editExpenseChit(): void
    {
        $this->I->wantTo('Update expense chit amount');
        $this->I->scrollTo('h4[id="chitList-payment"]');
        $this->I->clickStable('.ui--editChit');
        $this->I->waitForElement('[name="pid"]:not([value=""])');

        $this->I->fillFieldStable('input[name="items[0][price]"]', '121');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]');
        $this->I->clickStable('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function addIncomeChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('add income chit');

        $this->fillChitForm(new ChronosDate(), 'Účastnické poplatky', Operation::INCOME(), 'Přijmy od účastníků', 'Testovací skaut 2', '100');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]');
        $this->I->clickStable('input[name="send"]');

        $this->waitForBalance('-21,00');
    }

    private function createTwoLineExpenseChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('create expense chit');
        $this->fillChitForm(new ChronosDate(), 'Rohlíky', Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '50');
        $this->I->clickStable('input[name="items[addItem]"]');
        $this->I->waitForElement('input[name="items[1][remove]"]');
        $this->I->expect('Odebrat položku');
        $this->I->seeElement('input[name="items[1][remove]"]');
        $this->fillSecondChitForm('Hřebíky', Operation::EXPENSE(), 'Materiál', 'Testovací skaut', '50');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]');
        $this->I->clickStable('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function checkRemoveSecondLineExpenseChit(): void
    {
        $this->goToCashbookPage();

        $this->I->click('Nový doklad');
        $this->I->waitForText('Položky');
        $this->I->clickStable('input[name="items[addItem]"]');
        $this->I->expect('Odebrat položku');
        $this->I->seeElement('input[name="items[0][remove]"]');
        $this->I->click('input[name="items[0][remove]"]');
        $this->I->waitForElementNotVisible('input[name="items[0][remove]"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->dontSeeElement('//input[@type="submit" and @value="Odebrat položku"]');
        $this->I->seeElement('//input[@type="submit" and @value="Uložit"]');
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
        $this->cleanupEvent($this->I);
    }

    private function cleanupEvent(AcceptanceTester $I): void
    {
        $cancelButton = sprintf("//a[text()='%s']/ancestor::tr//a[@data-test='event-cancel-action'][1]", $this->eventName);

        try {
            $I->amOnPage('/');
            $I->click('Akce');
            $I->waitForText('Seznam akcí');
            $I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
            $I->waitForText($this->eventName, 5);

            if (count($I->grabMultiple($cancelButton)) === 0) {
                $this->eventCreated = false;

                return;
            }

            $cancelUrl = $I->grabAttributeFrom($cancelButton, 'href');
            $I->disablePopups();
            $I->amOnPage($cancelUrl);
            $I->waitForText('Seznam akcí', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
            $this->eventCreated = false;
        } catch (Throwable $e) {
            $I->comment(sprintf('Cleanup of event "%s" failed: %s', $this->eventName, $e->getMessage()));
        }
    }

    private function fillChitForm(ChronosDate $date, string $purpose, Operation $type, string $category, string $recipient, string $amount): void
    {
        $this->I->waitForElement('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->wantToTest('Uložit');
        $this->I->fillFieldStable('input[name="date"]', $date->format('d.m. Y'));
        $this->I->pressKey('body', [WebDriverKeys::ESCAPE]); // close datepicker
        $this->I->fillFieldStable('input[name="items[0][purpose]"]', $purpose);
        $this->I->selectOption('#chit-type', $type->equals(Operation::EXPENSE()) ? 'Výdaje' : 'Příjmy');
        $this->I->selectOption(sprintf('items[0][%sCategories]', $type->equals(Operation::EXPENSE()) ? 'expense' : 'income'), $category);
        $this->I->fillFieldStable('input[name="recipient"]', $recipient);
        $this->I->fillFieldStable('input[name="items[0][price]"]', $amount);
    }

    private function fillSecondChitForm(string $purpose, Operation $type, string $category, string $recipient, string $amount): void
    {
        $this->I->wantToTest('Uložit');
        $this->I->fillFieldStable('input[name="items[1][purpose]"]', $purpose);
        $this->I->selectOption(sprintf('items[1][%sCategories]', $type->equals(Operation::EXPENSE()) ? 'expense' : 'income'), $category);
        $this->I->fillFieldStable('input[name="recipient"]', $recipient);
        $this->I->fillFieldStable('input[name="items[1][price]"]', $amount);
    }

    private function waitForBalance(string $balance): void
    {
        $this->I->expectTo(sprintf('see %s CZK as final balance', $balance));
        $this->I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
        $this->I->waitForText($balance, AcceptanceTester::ELEMENT_LOAD_TIMEOUT, self::BALANCE_SELECTOR);
    }

    private function removeChit(int $position): void
    {
        $this->I->disablePopups();
        $this->I->clickStable('.ui--removeChit');
    }
}
