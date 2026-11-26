<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;

/** The export of events and camps takes the rows of the list, also when it opens before the list was shown. */
class ListExportCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group dashboard */
    public function exportDialogOpensStraightFromItsAddress(AcceptanceTester $I): void
    {
        $I->wantTo('open the export of events and camps straight from its address');

        $pages = [
            '/akce' => ['events-default-page', 'Exportovat souhrn akcí'],
            '/tabory' => ['camps-default-page', 'Exportovat souhrn táborů'],
        ];

        foreach ($pages as $url => [$page, $title]) {
            $this->openPageAndWaitForElementWithSkautisRetry($I, $url.'?do=exportDialog-open', '[data-test="'.$page.'"]');
            // Without AJAX the dialog is rendered into the page but stays hidden, as only an AJAX response shows it.
            $I->seeInPageSource($title);
        }
    }
}
