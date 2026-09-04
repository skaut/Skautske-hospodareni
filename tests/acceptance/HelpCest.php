<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

use function date;
use function preg_replace;
use function strtotime;
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
        $I->deleteFromDatabase('page_view_daily', ['page_key' => self::PAGE_KEY]);
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
        $youtubeTitle = 'Jak založit platební skupinu';

        $I->wantTo('write help in the administration and see it on the page');

        $I->amOnPage('/admin/napovedy/'.self::PAGE_KEY);
        $I->waitForElementVisible('[data-test="admin-help-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillFieldStable('[data-test="admin-help-form"] [data-test="help-lead"]', 'Vlastní proužek '.$heading);
        $I->fillFieldStable('[data-test="admin-help-form"] input[name="sections[0][heading]"]', $heading);
        $I->fillFieldStable('[data-test="admin-help-form"] textarea[name="sections[0][text]"]', $text);
        $I->fillFieldStable('[data-test="admin-help-form"] textarea[name="sections[0][items]"]', "první odrážka\ndruhá odrážka");
        $I->fillFieldStable('[data-test="help-youtube-title"]', $youtubeTitle);
        $I->fillFieldStable('[data-test="help-youtube-url"]', 'https://youtu.be/kfVsfOSbJY0?si=source&utm_source=newsletter');
        $I->clickStable('[data-test="admin-help-save"]');

        $I->waitForText('Nápověda byla uložena.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/napovedy/'.self::PAGE_KEY);
        $I->see('Náhled pro danou stránku', '[data-test="admin-help-preview"]');
        $I->see($heading, '[data-test="admin-help-preview-content"]');
        $I->see($text, '[data-test="admin-help-preview-content"]');
        $I->see($youtubeTitle, '[data-test="admin-help-preview-content"]');
        $I->dontSee('Vlastní proužek '.$heading, '[data-test="admin-help-preview"]');
        $I->seeElement('[data-test="admin-help-save-close"][value="Uložit a zavřít"]');
        $I->seeInDatabase('page_help', [
            'page_key' => self::PAGE_KEY,
            'youtube_title' => $youtubeTitle,
            'youtube_url' => 'https://www.youtube.com/watch?v=kfVsfOSbJY0',
        ]);

        $I->amOnPage(self::PAGE_URL);
        $I->waitForElementVisible('[data-test="help-sidebar"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see($heading, '[data-test="help-content"]');
        $I->see($text, '[data-test="help-content"]');
        $I->see('první odrážka', '[data-test="help-content"]');
        $I->see('Vlastní proužek '.$heading, '.page-lead');
        $I->see($youtubeTitle, '[data-test="help-youtube-link"]');
        $I->seeElement('[data-test="help-youtube-link"][href="https://www.youtube.com/watch?v=kfVsfOSbJY0"][target="_blank"][rel~="nofollow"][title="'.$youtubeTitle.'"]');

        $expandedVideoAlignment = $I->executeJS(<<<'JS'
const icon = document.querySelector('[data-test="help-youtube-icon"]')?.getBoundingClientRect();
const title = document.querySelector('[data-test="help-youtube-title"]')?.getBoundingClientRect();

return {
    iconCenter: icon === undefined ? null : icon.top + icon.height / 2,
    titleCenter: title === undefined ? null : title.top + title.height / 2,
};
JS);
        Assert::assertEqualsWithDelta($expandedVideoAlignment['iconCenter'], $expandedVideoAlignment['titleCenter'], 0.5);

        $I->clickStable('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "true"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="help-youtube-link"]');

        $iconSizes = $I->executeJS(<<<'JS'
const titleIcon = document.querySelector('[data-test="help-title-icon"]')?.getBoundingClientRect();
const youtubeIcon = document.querySelector('[data-test="help-youtube-icon"]')?.getBoundingClientRect();
const title = document.querySelector('[data-test="help-youtube-title"]');

return {
    titleIcon: {width: titleIcon?.width, height: titleIcon?.height},
    youtubeIcon: {width: youtubeIcon?.width, height: youtubeIcon?.height},
    titleVisible: title !== null && window.getComputedStyle(title).display !== 'none',
};
JS);
        Assert::assertSame($iconSizes['titleIcon'], $iconSizes['youtubeIcon']);
        Assert::assertFalse($iconSizes['titleVisible']);

        $I->clickStable('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "false"', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see($youtubeTitle, '[data-test="help-youtube-link"]');

        $I->wantTo('remove the help again by clearing the form');

        $I->amOnPage('/admin/napovedy/'.self::PAGE_KEY);
        $I->waitForElementVisible('[data-test="admin-help-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillFieldStable('[data-test="admin-help-form"] [data-test="help-lead"]', '');
        $I->fillFieldStable('[data-test="help-youtube-title"]', '');
        $I->fillFieldStable('[data-test="help-youtube-url"]', '');
        $I->clickStable('[data-test="admin-help-form"] [data-test="help-section-remove-0"]');
        $I->clickStable('[data-test="admin-help-form"] [name=send]');

        $I->waitForText('Nápověda byla odebrána', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="admin-help-list"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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

    /**
     * The page list doubles as the place to see which pages are worth a text,
     * which is what the removed Google Analytics never told anyone.
     *
     * @group help
     */
    public function pageListShowsHowMuchEachPageIsUsed(): void
    {
        $I = $this->I;

        $I->wantTo('see how much a page is used next to its help');

        $I->haveInDatabase('page_view_daily', [
            'page_key' => self::PAGE_KEY,
            'day' => date('Y-m-d', strtotime('-10 days')),
            'views' => 4242,
        ]);

        $I->amOnPage('/admin/napovedy');
        $I->waitForElementVisible('[data-test="admin-help-list"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        // The column is a 90 day sum, and the other scenarios open this very page,
        // so the only stable expectation is that the seeded views are included.
        $shown = (int) preg_replace('~\D~', '', $I->grabTextFrom('[data-test="admin-help-views-'.self::PAGE_KEY.'"]'));
        Assert::assertGreaterThanOrEqual(4242, $shown);
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
