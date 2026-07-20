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
        $I->waitForElementVisible('[data-test="unit-cashbook-page"], [data-test="unit-no-cashbook"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->amGoingTo('Create first unit cashbook - for current year');
        $this->createCashbookForFirstAvailableYear($I, true);

        $I->amGoingTo('Create cashbook for different year');
        $this->createCashbookForFirstAvailableYear($I, false);
    }

    private function createCashbookForFirstAvailableYear(AcceptanceTester $I, bool $allowEmptyStateButton): void
    {
        $year = null;

        $this->runWithSkautisRetry(
            $I,
            function () use ($I, $allowEmptyStateButton, &$year): string {
                $I->amOnPage('/jednotka');
                $I->waitForElementVisible(
                    '[data-test="unit-cashbook-page"], [data-test="unit-no-cashbook"]',
                    AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
                );

                if ($allowEmptyStateButton) {
                    $this->openCreateDialog($I);
                } else {
                    $this->openCreateFromMenu($I);
                }

                $dialogState = $I->waitForPageTextOrSkautisConnectionError('Vyberte rok');
                if ($dialogState !== AcceptanceTester::PAGE_STATE_EXPECTED) {
                    return $dialogState;
                }

                $year = $this->selectFirstAvailableYear($I);
                $I->click('Založit', '.modal-footer');

                return $I->waitForPageTextOrSkautisConnectionError('Pokladní kniha byla vytvořena');
            },
            'creating unit cashbook',
            'page text "Pokladní kniha byla vytvořena"',
        );

        $I->see('Pokladní kniha byla vytvořena');
        if ($year !== null) {
            $I->see((string) $year);
        }
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
        $I->waitForText('Přidat pokladní knihu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('Přidat pokladní knihu');
    }

    private function selectFirstAvailableYear(AcceptanceTester $I): int
    {
        // Grab the first available option value (years with existing cashbooks are removed)
        $year = (int) $I->executeJS(
            'return document.querySelector("select[name=\'year\'] option:not([value=\'\'])").value',
        );
        $I->selectOption('Rok', (string) $year);

        return $year;
    }
}
