<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function time;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class HelpCest extends BaseAcceptanceCest
{
    private const ADMIN_USER_ID = 2465;

    /**
     * A page that no migration seeds help for, so the scenario owns its whole
     * lifecycle and stays independent of what content the application ships with.
     */
    private const PAGE_KEY = 'Settings:User:default';
    private const PAGE_URL = '/nastaveni/uzivatel';
    private const PAGE_MARKER = '[data-test="settings-user-page"]';

    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $I->haveInDatabase('admin_user', [
            'user_id' => self::ADMIN_USER_ID,
            'created_at' => '2026-03-19 12:00:00',
        ]);
    }

    public function _after(AcceptanceTester $I): void
    {
        $I->deleteFromDatabase('page_help', ['page_key' => self::PAGE_KEY]);
    }

    /** @group help */
    public function seededHelpIsRenderedFromTheDatabase(): void
    {
        $I = $this->I;

        $I->wantTo('see help that is stored in the database');

        $I->amOnPage('/cestaky/vozidla');
        $I->waitForElementVisible('[data-test="help-sidebar"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Vozový park', '[data-test="help-content"]');
        $I->see('Archivace', '[data-test="help-content"]');
    }

    /** @group help */
    public function pageWithoutHelpHasNoPanel(): void
    {
        $I = $this->I;

        $I->wantTo('see no help panel on a page that has none');

        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible(self::PAGE_MARKER, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeElement('[data-test="help-sidebar"]');
    }

    /** @group help */
    public function adminWritesHelpAndItAppearsOnThePage(): void
    {
        $I = $this->I;
        $heading = 'Platnost tři roky';
        $text = 'Testovací nápověda '.time();

        $I->wantTo('write help in the administration and see it on the page');

        $I->amOnPage('/admin/napovedy/'.self::PAGE_KEY);
        $I->waitForElementVisible('[data-test="admin-help-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillFieldStable('[data-test="admin-help-form"] [data-test="help-lead"]', 'Vlastní proužek '.$heading);
        $I->fillFieldStable('[data-test="admin-help-form"] input[name="sections[0][heading]"]', $heading);
        $I->fillFieldStable('[data-test="admin-help-form"] textarea[name="sections[0][text]"]', $text);
        $I->fillFieldStable('[data-test="admin-help-form"] textarea[name="sections[0][items]"]', "první odrážka\ndruhá odrážka");
        $I->clickStable('[data-test="admin-help-form"] [name=send]');

        $I->waitForText('Nápověda byla uložena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('page_help', ['page_key' => self::PAGE_KEY]);

        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible('[data-test="help-sidebar"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see($heading, '[data-test="help-content"]');
        $I->see($text, '[data-test="help-content"]');
        $I->see('první odrážka', '[data-test="help-content"]');
        $I->see('Vlastní proužek '.$heading, '.page-lead');

        $I->wantTo('remove the help again by clearing the form');

        $I->amOnPage('/admin/napovedy/'.self::PAGE_KEY);
        $I->waitForElementVisible('[data-test="admin-help-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillFieldStable('[data-test="admin-help-form"] [data-test="help-lead"]', '');
        $I->clickStable('[data-test="admin-help-form"] [data-test="help-section-remove-0"]');
        $I->clickStable('[data-test="admin-help-form"] [name=send]');

        $I->waitForText('Nápověda byla odebrána', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeInDatabase('page_help', ['page_key' => self::PAGE_KEY]);

        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible(self::PAGE_MARKER, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeElement('[data-test="help-sidebar"]');
    }

    /**
     * The editor opens with one block on an empty form and reveals the rest on demand.
     * Every block is always posted, so hiding is only visual.
     *
     * @group help
     */
    public function editorStartsWithOneBlockAndAddsMoreOnDemand(): void
    {
        $I = $this->I;

        $I->wantTo('open the editor with a single block and add another one');

        $I->amOnPage('/admin/napovedy/'.self::PAGE_KEY);
        $I->waitForElementVisible('[data-test="help-section-0"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->dontSeeElement('[data-test="help-section-1"]');
        $I->seeElement('[data-test="help-section-add"]');

        $I->clickStable('[data-test="help-section-add"]');
        $I->waitForElementVisible('[data-test="help-section-1"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->clickStable('[data-test="help-section-remove-1"]');
        $I->waitForElementNotVisible('[data-test="help-section-1"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="help-section-0"]');
    }

    /**
     * The panel sits beside the content on desktop and moves below it on a phone.
     * Measured on the real page, because the breakpoint lives in CSS only.
     *
     * @group help
     */
    public function helpPanelReflowsBelowContentOnNarrowScreens(): void
    {
        $I = $this->I;

        $I->wantTo('see the help panel beside the content on desktop and below it on a phone');

        $I->amOnPage('/cestaky/vozidla');
        $I->waitForElementVisible('[data-test="help-sidebar"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // Window width, not viewport width: the browser chrome and scrollbar make the
        // viewport narrower, so the values stay clear of the 992 px breakpoint.
        foreach ([[1440, 'beside'], [1200, 'beside'], [900, 'below'], [768, 'below'], [375, 'below']] as [$width, $expected]) {
            $I->resizeWindow($width, 900);
            $I->wait(1);

            $layout = $I->executeJS(<<<'JS'
const main = document.querySelector('.design-help-main');
const aside = document.querySelector('[data-test="help-sidebar"]');
const m = main.getBoundingClientRect();
const a = aside.getBoundingClientRect();
return {
    position: a.left >= m.right - 1 ? 'beside' : (a.top >= m.bottom - 1 ? 'below' : 'overlapping'),
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    asideVisible: a.width > 0 && a.height > 0,
    viewport: window.innerWidth,
};
JS);

            $I->comment('RESPONSIVE okno='.$width.' viewport='.$layout['viewport'].' pozice='.$layout['position'].' přetečení='.$layout['overflow']);
            Assert::assertSame($expected, $layout['position'], 'Panel na šířce '.$width.' px');
            Assert::assertSame(0, $layout['overflow'], 'Vodorovné přetečení na šířce '.$width.' px');
            Assert::assertTrue($layout['asideVisible'], 'Panel je vidět na šířce '.$width.' px');
        }
    }

    /** @group help */
    public function unknownPageKeyIsRejected(): void
    {
        $I = $this->I;

        $I->wantTo('get a 404 for a page key that does not exist');

        $I->amOnPage('/admin/napovedy/Travel:NoSuchPresenter:default');
        $I->waitForText('404', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }
}
