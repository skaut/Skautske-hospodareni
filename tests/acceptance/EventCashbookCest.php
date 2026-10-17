<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use Cake\Chronos\ChronosDate;
use Facebook\WebDriver\WebDriverKeys;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;

use function count;
use function date;
use function json_encode;
use function sprintf;
use function time;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class EventCashbookCest extends BaseAcceptanceCest
{
    private const BALANCE_SELECTOR = '.ui--balance';
    private const EVENTS_LIST_SELECTOR = '[data-test="events-default-page"]';
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
        $this->checkEventFileActions();
        $this->goToCashbookPage();
        $this->createExpenseChit();
        $this->checkChitFileActions();
        $this->editExpenseChit();
        $this->addIncomeChit();
        $this->createTwoLineExpenseChit();
        $this->removeChits();
        $this->checkRemoveSecondLineExpenseChit();
        $this->editImportedParticipantIncomeChit();
        $this->cancelEvent();
    }

    private function createEvent(): void
    {
        $this->I->amGoingTo('create event');

        $this->I->click('Založit novou akci');
        $this->I->waitForText('Název akce', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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

    private function checkEventFileActions(): void
    {
        $I = $this->I;
        $I->amGoingTo('check event report and participant exports');

        $I->seePdfFileAction('event-report');
        $I->seePdfFileAction('event-print-all');
        $I->seeNoHorizontalOverflowOnPhone('detail akce', '[data-test="event-report-toggle"]');
        $I->openPdfFileActionInNewTab('event-report', '/report');

        $I->click('Seznam účastníků');
        $I->seePdfFileAction('participants-export-pdf');
        $I->seeDownloadOnlyFileAction('participants-export-excel');
        $I->seeNoHorizontalOverflowOnPhone('seznam účastníků');
    }

    private function checkChitFileActions(): void
    {
        $I = $this->I;
        $I->amGoingTo('check chit and cashbook downloads');

        $chitId = (string) $I->grabValueFrom('input[name="chits-cash[]"]');
        $I->seePdfFileAction('chit-pdf-'.$chitId);
        $I->seePdfFileAction('cashbook-cash-pdf');
        $I->seeDownloadOnlyFileAction('cashbook-cash-excel');
        $I->seeDownloadOnlyFileAction('cashbook-cash-excel-categories');
        $I->seeDownloadOnlyFileAction('cashbook-cash-scans');
        $I->seeNoHorizontalOverflowOnPhone('evidence plateb – doklad', '[data-test="chit-pdf-'.$chitId.'-toggle"]');
        $I->seeNoHorizontalOverflowOnPhone('evidence plateb – pokladní kniha', '[data-test="cashbook-cash-pdf-toggle"]');

        // Hromadný tisk vybraných dokladů: stažení zůstává výchozí akcí, otevření běží v nové kartě.
        $I->checkOption(sprintf('input[name="chits-cash[]"][value="%s"]', $chitId));
        $I->seePdfFileAction('chits-mass-pdf-cash');
        $I->seeNoHorizontalOverflowOnPhone('evidence plateb – vybrané doklady', '[data-test="chits-mass-pdf-cash-toggle"]');

        $downloadUrl = $I->clickAndGrabAjaxRedirect('[data-test="chits-mass-pdf-cash"]');
        Assert::assertStringContainsString('print-chits', $downloadUrl);
        Assert::assertStringContainsString('download=1', $downloadUrl);
        Assert::assertStringStartsWith('attachment;', $I->grabContentDisposition($downloadUrl));
        $I->seeElement('[data-test="chits-mass-pdf-cash"]');

        $openedUrl = $I->openPdfFileActionInNewTab('chits-mass-pdf-cash', 'print-chits');
        Assert::assertStringNotContainsString('download=1', $openedUrl);
        Assert::assertStringStartsWith('inline;', $I->grabContentDisposition($openedUrl));
    }

    private function goToCashbookPage(): void
    {
        $this->I->amGoingTo('open cashbook');

        $cashbookButton = 'Evidence plateb';
        $this->I->waitForText($cashbookButton, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->click($cashbookButton);

        $this->I->waitForText(self::NO_CHITS_MESSAGE, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $this->I->waitForElement('[name="pid"]:not([value=""])', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->see('Upravit doklad číslo : ---', '#chitFormHeader');

        $this->I->fillFieldStable('input[name="items[0][price]"]', '121');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->clickStable('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function addIncomeChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('add income chit');

        $this->fillChitForm(new ChronosDate(), 'Účastnické poplatky', Operation::INCOME(), 'Od dětí a roverů', 'Testovací skaut 2', '100');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->clickStable('input[name="send"]');

        $this->waitForBalance('-21,00');
    }

    private function createTwoLineExpenseChit(): void
    {
        $this->I->click('Nový doklad');
        $this->I->amGoingTo('create expense chit');
        $this->fillChitForm(new ChronosDate(), 'Rohlíky', Operation::EXPENSE(), 'Potraviny', 'Testovací skaut', '50');
        $this->I->clickStable('input[name="items[addItem]"]');
        $this->I->waitForElement('input[name="items[1][remove]"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->expect('Odebrat položku');
        $this->I->seeElement('input[name="items[1][remove]"]');
        $this->fillSecondChitForm('', Operation::EXPENSE(), 'Materiál', 'Testovací skaut', '50');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->clickStable('input[name="send"]');
        $this->I->waitForText('Zadejte účel výplaty', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->seeElement('input[name="send"]');
        $this->I->seeInField('input[name="items[0][purpose]"]', 'Rohlíky');
        $this->I->seeInField('input[name="recipient"]', 'Testovací skaut');
        $this->I->seeInField('input[name="items[0][price]"]', '50');
        $this->I->seeInField('input[name="items[1][price]"]', '50');

        $this->I->fillFieldStable('input[name="items[1][purpose]"]', 'Hřebíky');
        $this->I->scrollTo('input[name="send"]');
        $this->I->waitForElementClickable('input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->clickStable('input[name="send"]');
        $this->waitForBalance('-121,00');
    }

    private function checkRemoveSecondLineExpenseChit(): void
    {
        $this->goToCashbookPage();

        $this->I->click('Nový doklad');
        $this->I->waitForText('Položky', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $this->I->waitForText(self::NO_CHITS_MESSAGE, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    private function cancelEvent(): void
    {
        $this->I->amGoingTo('cancel the event');
        $this->cleanupEvent($this->I);
    }

    private function editImportedParticipantIncomeChit(): void
    {
        $this->I->amGoingTo('edit imported participant income chit');

        $this->I->click('Seznam účastníků');
        $this->I->waitForElement('[data-test="event-participant-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->clickStable('a[href="#addParticipant"]');
        $this->I->waitForElement('#addParticipant input[name="firstName"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $this->I->fillFieldStable('#addParticipant input[name="firstName"]', 'Acceptance', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->fillFieldStable('#addParticipant input[name="lastName"]', 'HPD participant', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->fillFieldStable('#addParticipant input[name="street"]', 'Testovací 1', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->fillFieldStable('#addParticipant input[name="city"]', 'Praha', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->fillFieldStable('#addParticipant input[name="postcode"]', '11000', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->checkOption('#addParticipant input[name="sex"][value="male"]');
        $this->I->clickStable('#addParticipant input[name="send"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $this->I->waitForText('HPD participant Acceptance', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '#participants-list');
        $this->I->clickStable('#participants-list a[title="Upravit účastníka"]');
        $this->I->waitForElement('input[name="payment"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->fillFieldStable('input[name="payment"]', '125', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->clickStable('input[name="save"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $this->I->waitForText('125', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '#participants-list');

        $this->I->click('Evidence plateb');
        $this->I->waitForElement('[data-test="event-cashbook-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->I->click('Načíst příjmy od účastníků');
        $this->I->waitForText('účastnické příspěvky', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Kliknutí na „Upravit“ musí otevřít formulář s načteným dokladem a jeho kategorií.
        $this->openImportedParticipantIncomeChitForEdit();
        $this->I->seeInField('input[name="items[0][purpose]"]', 'účastnické příspěvky');
        $this->I->seeInField('input[name="items[0][price]"]', '125.00');

        // Standardní úprava: datum, pokladna/banka, číslo, Komu/Od, účel i částka.
        $updatedDate = new ChronosDate('tomorrow');
        $this->I->fillFieldStable('input[name="date"]', $updatedDate->format('d.m. Y'));
        $this->I->pressKey('body', [WebDriverKeys::ESCAPE]);
        $this->I->checkOption('input[name="paymentMethod"][value="bank"]');
        $this->I->fillFieldStable('input[name="num"]', 'HPD1');
        $this->I->fillFieldStable('input[name="recipient"]', 'Účastníci akce');
        $this->I->fillFieldStable('input[name="items[0][purpose]"]', 'účastnické příspěvky upravené');
        $this->I->fillFieldStable('input[name="items[0][price]"]', '130');
        $this->I->clickStable('input[name="send"]');
        $this->I->waitForText('Paragon byl upraven.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $this->openPageAndWaitForElementWithSkautisRetry($this->I, $this->I->grabFromCurrentUrl(), '[data-test="event-cashbook-page"]');

        // Po uložení je doklad v Bance se všemi změněnými údaji.
        $this->I->waitForJS(
            'const bankHeading = [...document.querySelectorAll("#chitList-payment")]'
            .'.find(heading => heading.textContent.includes("Banka"));'
            .'const row = [...(bankHeading?.closest(".card")?.querySelectorAll("tr") ?? [])]'
            .'.find(tr => tr.textContent.includes("účastnické příspěvky upravené"));'
            .'return row !== undefined'
            .' && row.textContent.includes('.json_encode($updatedDate->format('d.m.Y')).')'
            .' && row.textContent.includes("HPD1")'
            .' && row.textContent.includes("Účastníci akce")'
            .' && row.textContent.includes("130,00");',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        // Uložené hodnoty se načtou i při další úpravě.
        $this->openImportedParticipantIncomeChitForEdit();
        $this->I->seeCheckboxIsChecked('input[name="paymentMethod"][value="bank"]');
        $this->I->seeInField('input[name="num"]', 'HPD1');
        $this->I->seeInField('input[name="recipient"]', 'Účastníci akce');
        $this->I->seeInField('input[name="items[0][purpose]"]', 'účastnické příspěvky upravené');
        $this->I->seeInField('input[name="items[0][price]"]', '130');
    }

    private function openImportedParticipantIncomeChitForEdit(): void
    {
        $this->I->clickStable('//tr[.//td[contains(@title, "účastnické příspěvky")]]//a[contains(@class, "ui--editChit")]');
        $this->I->waitForElement('[name="pid"]:not([value=""])', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $selectedCategory = $this->I->executeJs(
            'return document.querySelector("select[name=\'items[0][incomeCategories]\']")?.value;',
        );
        if ($selectedCategory !== (string) ICategory::CATEGORY_HPD_ID) {
            throw new RuntimeException('Imported participant income chit does not retain the HPD category in the edit form.');
        }
    }

    private function cleanupEvent(AcceptanceTester $I): void
    {
        $cancelButton = sprintf("//a[text()='%s']/ancestor::tr//a[@data-test='event-cancel-action'][1]", $this->eventName);

        try {
            $this->openEventList($I);
            $I->executeJs('window.scrollTo(0, document.body.scrollHeight);');
            $I->waitForText($this->eventName, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

            if (count($I->grabMultiple($cancelButton)) === 0) {
                $this->eventCreated = false;

                return;
            }

            $cancelUrl = $I->grabAttributeFrom($cancelButton, 'href');
            if ($cancelUrl === null) {
                throw new RuntimeException('Odkaz pro zrušení akce nemá cílovou adresu.');
            }

            $I->disablePopups();
            $I->amOnPage($cancelUrl);
            $this->openEventList($I);
            $I->dontSee($this->eventName, self::EVENTS_LIST_SELECTOR);
            $this->eventCreated = false;
        } catch (Throwable $e) {
            $I->comment(sprintf('Cleanup of event "%s" failed: %s', $this->eventName, $e->getMessage()));
        }
    }

    private function openEventList(AcceptanceTester $I): void
    {
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/akce', self::EVENTS_LIST_SELECTOR);
        $I->waitForDocumentReady();
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
        $this->I->waitForJS(
            'const balance = document.querySelector('.json_encode(self::BALANCE_SELECTOR).');'
            .'return balance !== null && balance.textContent.includes('.json_encode($balance).');',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );
    }

    private function removeChit(int $position): void
    {
        $this->I->disablePopups();
        $this->I->clickStable('.ui--removeChit');
        $this->I->waitForDocumentReady();
    }
}
