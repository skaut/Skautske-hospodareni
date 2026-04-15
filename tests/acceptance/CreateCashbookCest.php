<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;

use function str_contains;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class CreateCashbookCest extends BaseAcceptanceCest
{
    /**
     * @desc Create cashbooks for current and next year
     * @group cashbooks
     */
    public function testCreateCashbooks(AcceptanceTester $I): void
    {
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);

        $I->click('Jednotka');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"], [data-test="unit-no-cashbook"]', 10);

        $I->amGoingTo('Create first unit cashbook - for current year');
        $this->openCreateDialog($I);
        $this->selectFirstAvailableYear($I);

        $I->amGoingTo('Create cashbook for different year');
        $this->openCreateFromMenu($I);
        $this->selectFirstAvailableYear($I);
    }

    private function openCreateDialog(AcceptanceTester $I): void
    {
        $source = $I->grabPageSource();

        if (str_contains($source, 'Založit novou pokladní knihu')) {
            // Empty state — big CTA button
            $I->click('Založit novou pokladní knihu');
        } else {
            // Cashbook exists — use dropdown menu
            $this->openCreateFromMenu($I);
        }
    }

    private function openCreateFromMenu(AcceptanceTester $I): void
    {
        $I->click('#unit-cashbook-menu');
        $I->waitForText('Přidat pokladní knihu');
        $I->click('Přidat pokladní knihu');
    }

    private function selectFirstAvailableYear(AcceptanceTester $I): void
    {
        $I->waitForText('Vyberte rok');
        // Grab the first available option value (years with existing cashbooks are removed)
        $year = (int) $I->executeJS(
            'return document.querySelector("select[name=\'year\'] option:not([value=\'\'])").value',
        );
        $I->selectOption('Rok', (string) $year);
        $I->click('Založit', '.modal-footer');

        $I->waitForText('Pokladní kniha byla vytvořena');
        $I->see('Pokladní kniha byla vytvořena');
        $I->see((string) $year);
    }
}
