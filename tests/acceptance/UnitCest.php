<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

final class UnitCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group unit */
    public function openCashbookFromUnitSectionViaCanonicalUrl(): void
    {
        $I = $this->I;

        $I->wantTo('open the unit cashbook via the canonical unit section route');

        $I->amOnPage('/jednotka');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/jednotka/'.AcceptanceTester::UNIT_ID.'/kniha');

        $href = $I->grabAttributeFrom('[data-test="unit-subnav-cashbook"]', 'href');
        Assert::assertMatchesRegularExpression('~^/jednotka/\d+/kniha(?:\?rok=\d+)?$~', $href);

        $I->seeElement('.active [data-test="unit-subnav-cashbook"]');
        $I->seeElement('.active [data-test="global-nav-unit"]');
    }

    /** @group unit */
    public function openBudgetFromUnitSectionViaCanonicalUrl(): void
    {
        $I = $this->I;

        $I->wantTo('open the unit budget via the new canonical unit section route');

        $I->amOnPage('/jednotka');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $href = $I->grabAttributeFrom('[data-test="unit-subnav-budget"]', 'href');
        Assert::assertMatchesRegularExpression('~^/jednotka/\d+/rozpocet(?:\?rok=\d+)?$~', $href);

        $I->clickStable('[data-test="unit-subnav-budget"]');
        $I->waitForElementVisible('[data-test="unit-budget-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/jednotka/'.AcceptanceTester::UNIT_ID.'/rozpocet');
        $I->seeElement('.active [data-test="unit-subnav-budget"]');
        $I->seeElement('.active [data-test="global-nav-unit"]');
    }

    /** @group unit */
    public function openChitsFromUnitSectionViaCanonicalUrl(): void
    {
        $I = $this->I;

        $I->wantTo('open the unit chits overview via the new canonical unit section route');

        $I->amOnPage('/jednotka');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $href = $I->grabAttributeFrom('[data-test="unit-subnav-chits"]', 'href');
        Assert::assertMatchesRegularExpression('~^/jednotka/\d+/paragony(?:\?rok=\d+)?$~', $href);

        $I->clickStable('[data-test="unit-subnav-chits"]');
        $I->waitForElementVisible('[data-test="unit-chits-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/jednotka/'.AcceptanceTester::UNIT_ID.'/paragony');
        $I->seeElement('.active [data-test="unit-subnav-chits"]');
        $I->seeElement('.active [data-test="global-nav-unit"]');
    }

    /** @group unit */
    public function switchChitFilterOnCanonicalUnitRoute(): void
    {
        $I = $this->I;

        $I->wantTo('switch the unit chit filter without leaving the canonical unit route');

        $I->amOnPage('/jednotka/'.AcceptanceTester::UNIT_ID.'/paragony');
        $I->waitForElementVisible('[data-test="unit-chits-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->clickStable('[data-test="unit-chits-filter-toggle"]');
        $I->clickStable('[data-test="unit-chits-filter-all"]');

        $I->waitForText('Všechny paragony', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="unit-chits-filter-toggle"]');
        $I->seeInCurrentUrl('/jednotka/'.AcceptanceTester::UNIT_ID.'/paragony');
        $I->seeInCurrentUrl('onlyUnlocked=0');
        $I->seeElement('.active [data-test="unit-subnav-chits"]');
        $I->seeElement('.active [data-test="global-nav-unit"]');
    }

    /** @group unit */
    public function budgetCategoryLifecycle(): void
    {
        $I = $this->I;

        $I->wantTo('create a budget category and verify it appears');

        $year = (int) date('Y');
        $categoryLabel = 'Selenium Test '.time();

        // Navigate to budget add page
        $I->amOnPage('/jednotka/'.AcceptanceTester::UNIT_ID.'/rozpocet');
        $I->waitForElementVisible('[data-test="unit-budget-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="unit-budget-add-link"]');
        $I->waitForElementVisible('[data-test="unit-budget-add-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Fill and submit form
        $I->fillField('#frm-addCategoryForm-label', $categoryLabel);
        $I->selectOption('#form-select-type', 'Příjmy');
        $I->fillField('#frm-addCategoryForm-year', (string) $year);
        $I->click('Založit kategorii');

        // Verify redirect to budget list and category is visible
        $I->waitForElementVisible('[data-test="unit-budget-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see($categoryLabel);

        // Cleanup — delete via direct DB
        $I->seeInDatabase('ac_unit_budget_category', ['label' => $categoryLabel, 'unit_id' => AcceptanceTester::UNIT_ID]);
        $I->deleteFromDatabase('ac_unit_budget_category', ['label' => $categoryLabel, 'unit_id' => AcceptanceTester::UNIT_ID]);
    }

    /** @group unit */
    public function switchCashbookYearViaDropdown(): void
    {
        $I = $this->I;

        $I->wantTo('switch cashbook year via the year dropdown in submenu');

        $I->amOnPage('/jednotka/'.AcceptanceTester::UNIT_ID.'/kniha');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Click the year dropdown in submenu
        $I->click('#unit-cashbook-list');
        $I->waitForElementVisible('.dropdown-menu[aria-labelledby="unit-cashbook-list"]', 5);

        // The dropdown should show at least one year item
        $I->seeElement('.dropdown-menu[aria-labelledby="unit-cashbook-list"] .dropdown-item');
    }

    /** @group unit */
    public function switchChitYearViaButtons(): void
    {
        $I = $this->I;

        $I->wantTo('switch chit view to a different year via year buttons');

        $I->amOnPage('/jednotka/'.AcceptanceTester::UNIT_ID.'/paragony');
        $I->waitForElementVisible('[data-test="unit-chits-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // The current year button should be active
        $currentYear = date('Y');
        $I->see($currentYear, '.btn.active');

        // Click previous year
        $prevYear = (string) ((int) $currentYear - 1);
        $I->clickWithLeftButton('//a[contains(@class,"btn") and normalize-space()="'.$prevYear.'"]');
        $I->waitForElementVisible('[data-test="unit-chits-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('rok='.$prevYear);
    }

    /** @group unit */
    public function unitDropdownShowsAvailableUnits(): void
    {
        $I = $this->I;

        $I->wantTo('verify the unit switch dropdown shows available units');

        $I->amOnPage('/jednotka/'.AcceptanceTester::UNIT_ID.'/kniha');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // The unit switcher dropdown should be visible in the submenu
        $I->seeElement('[data-test="unit-submenu"] .dropdown');

        // Click the unit dropdown toggle
        $I->clickStable('[data-test="unit-submenu"] .dropdown .dropdown-toggle');
        $I->waitForElementVisible('[data-test="unit-submenu"] .dropdown .dropdown-menu', 5);

        // Should see at least one unit item
        $I->seeElement('[data-test="unit-submenu"] .dropdown .dropdown-item');
    }
}
