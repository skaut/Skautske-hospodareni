<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use DateTimeImmutable;

final class AnnouncementCest extends BaseAcceptanceCest
{
    private const ADMIN_USER_ID = 2465;

    private AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group announcements */
    public function nonAdminCannotManageAnnouncements(): void
    {
        $this->I->deleteFromDatabase('system_user_role', ['user_id' => self::ADMIN_USER_ID]);
        $this->I->amOnPage('/admin/oznameni');
        $this->I->seeInCurrentUrl('/');
        $this->I->dontSeeElement('[data-test="admin-announcements-page"]');
    }

    /** @group announcements */
    public function announcementManagerCanManageAnnouncementsOnly(): void
    {
        $I = $this->I;
        $I->deleteFromDatabase('system_user_role', ['user_id' => self::ADMIN_USER_ID]);
        $I->haveInDatabase('system_user_role', [
            'user_id' => self::ADMIN_USER_ID,
            'role' => 'announcement_manager',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $I->amOnPage('/admin');
        $I->waitForElementVisible('[data-test="admin-announcements-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInCurrentUrl('/admin/oznameni');
        $I->dontSeeElement('[data-test="admin-nav-users"]');
        $I->dontSeeElement('[data-test="admin-nav-help"]');

        $I->amOnPage('/admin/oznameni/nove');
        $I->waitForElementVisible('[data-test="admin-announcements-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $title = 'Správce oznámení '.time();
        $I->fillField('input[name="title"]', $title);
        $I->fillField('textarea[name="message"]', 'Zpráva vytvořená správcem oznámení.');
        $I->selectOption('select[name="category"]', 'info');
        $I->clickStable('[data-test="announcement-form-submit"]');
        $I->waitForElementVisible('[data-test="admin-announcements-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('announcement', ['title' => $title]);

        $I->amOnPage('/admin/uzivatele');
        $I->dontSeeElement('[data-test="admin-users-page"]');

        $I->deleteFromDatabase('announcement', ['title' => $title]);
        $I->deleteFromDatabase('system_user_role', ['user_id' => self::ADMIN_USER_ID]);
    }

    /** @group announcements */
    public function adminCrudAndDashboardListAcceptance(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $suffix = (string) time();
        $title = 'Acceptance oznámení '.$suffix;
        $I->deleteFromDatabase('announcement', ['title' => $title]);
        $I->amOnPage('/admin/oznameni/nove');
        $I->waitForElementVisible('[data-test="admin-announcements-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillField('input[name="title"]', $title);
        $I->fillField('textarea[name="message"]', 'Text zprávy pro acceptance test.');
        $I->selectOption('select[name="category"]', 'news');
        $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d\TH:i');
        $I->fillFieldStable('input[name="expiresAt"]', $expiresAt);
        $I->seeInField('input[name="expiresAt"]', $expiresAt);
        $I->clickStable('[data-test="announcement-form-submit"]');
        $I->waitForText($title, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="admin-announcements-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('announcement', [
            'title' => $title,
            'category_code' => 'news',
            'hidden' => 0,
            'expires_at' => str_replace('T', ' ', $expiresAt).':00',
        ]);

        $id = (int) $I->grabFromDatabase('announcement', 'id', ['title' => $title]);
        $I->amOnPage('/admin/oznameni/'.$id.'/upravit');
        $I->waitForElementVisible('[data-test="admin-announcements-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('input[name="title"]', $title.' upraveno');
        $updatedTitle = $title.' upraveno';
        $I->fillField('textarea[name="message"]', 'Upravený text zprávy.');
        $I->selectOption('select[name="category"]', 'warning');
        $updatedExpiresAt = (new DateTimeImmutable('+31 days'))->format('Y-m-d\TH:i');
        $I->fillFieldStable('input[name="expiresAt"]', $updatedExpiresAt);
        $I->seeInField('input[name="expiresAt"]', $updatedExpiresAt);
        $I->clickStable('[data-test="announcement-form-submit"]');
        $I->waitForText($updatedTitle, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="admin-announcements-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('announcement', [
            'id' => $id,
            'title' => $updatedTitle,
            'category_code' => 'warning',
            'expires_at' => str_replace('T', ' ', $updatedExpiresAt).':00',
        ]);

        $toggleSelector = $this->announcementRowActionSelector($id, 'admin-announcement-toggle');
        $I->clickStable($toggleSelector);
        $I->seeInDatabase('announcement', ['id' => $id, 'hidden' => 1]);
        $I->clickStable($toggleSelector);
        $I->seeInDatabase('announcement', ['id' => $id, 'hidden' => 0]);

        $I->disablePopups();
        $I->clickStable($this->announcementRowActionSelector($id, 'admin-announcement-delete'));
        $I->waitForText('Oznámení bylo trvale smazáno.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeInDatabase('announcement', ['id' => $id]);

        $now = time();
        $titles = [];
        for ($index = 1; $index <= 4; ++$index) {
            $listTitle = 'Acceptance seznam '.$suffix.' '.$index;
            $titles[] = $listTitle;
            $I->haveInDatabase('announcement', [
                'title' => $listTitle,
                'message' => 'Zpráva '.$index,
                'category_code' => 'info',
                'published_at' => date('Y-m-d H:i:s', $now - $index * 60),
                'expires_at' => date('Y-m-d H:i:s', $now + 86400),
                'hidden' => 0,
            ]);
        }

        foreach (['expired' => -86400, 'hidden' => 86400, 'scheduled' => 86400] as $state => $offset) {
            $stateTitle = 'Acceptance '.$state.' '.$suffix;
            $titles[] = $stateTitle;
            $I->haveInDatabase('announcement', [
                'title' => $stateTitle,
                'message' => 'Zpráva mimo aktuální výpis',
                'category_code' => 'info',
                'published_at' => $state === 'scheduled' ? date('Y-m-d H:i:s', $now + $offset) : date('Y-m-d H:i:s', $now - 300),
                'expires_at' => $state === 'expired' ? date('Y-m-d H:i:s', $now + $offset) : date('Y-m-d H:i:s', $now + 86400),
                'hidden' => $state === 'hidden' ? 1 : 0,
            ]);
        }

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeNumberOfElements('[data-test="dashboard-announcement"]', 3);
        $I->seeElement('[data-test="dashboard-announcements-all"]');
        $I->seeElement('[data-test="dashboard-announcement"] i.fi-rr-info');
        $I->see($titles[0], '[data-test="dashboard-announcements"]');
        $I->dontSee($titles[3], '[data-test="dashboard-announcements"]');
        $I->clickStable('[data-test="dashboard-announcements-all"]');
        $I->waitForElementVisible('[data-test="announcements-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeNumberOfElements('[data-test="announcements-list"] tbody tr[data-id]', 4);
        $I->see('Zpráva 1', '[data-test="announcements-list"]');
        $I->seeElement('[data-test="announcements-list"] span[title="Informace"]');
        $I->dontSeeElement('[data-test="announcements-list"] tbody a[href^="/oznameni/"]');

        $I->deleteFromDatabase('announcement', ['title' => $titles[3]]);
        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeNumberOfElements('[data-test="dashboard-announcement"]', 3);
        $I->dontSeeElement('[data-test="dashboard-announcements-all"]');

        foreach ($titles as $listTitle) {
            $I->deleteFromDatabase('announcement', ['title' => $listTitle]);
        }
        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeElement('[data-test="dashboard-announcements"]');
        $I->amOnPage('/oznameni');
        $I->waitForElementVisible('[data-test="announcements-empty"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('Momentálně nejsou k dispozici žádná oznámení.');
    }

    /** @group announcements */
    public function dashboardShowsAllAnnouncementsLinkOnlyWhenThereAreMoreThanThreeVisible(): void
    {
        $I = $this->I;
        $I->deleteFromDatabase('announcement', []);
        $suffix = (string) time();
        $titles = [];

        for ($index = 1; $index <= 4; ++$index) {
            $title = 'Acceptance dashboard odkaz '.$suffix.' '.$index;
            $titles[] = $title;
            $I->haveInDatabase('announcement', [
                'title' => $title,
                'message' => 'Zpráva '.$index,
                'category_code' => 'info',
                'published_at' => date('Y-m-d H:i:s', time() - $index * 60),
                'expires_at' => date('Y-m-d H:i:s', time() + 86400),
                'hidden' => 0,
            ]);
        }

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeNumberOfElements('[data-test="dashboard-announcement"]', 3);
        $I->seeElement('[data-test="dashboard-announcements-all"]');

        $I->deleteFromDatabase('announcement', ['title' => $titles[3]]);
        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeNumberOfElements('[data-test="dashboard-announcement"]', 3);
        $I->dontSeeElement('[data-test="dashboard-announcements-all"]');

        $I->deleteFromDatabase('announcement', []);
    }

    /** @group announcements */
    public function announcementCanBeCreatedAndKeptVisibleWithoutExpiration(): void
    {
        $I = $this->I;
        $this->becomeAdmin();

        $title = 'Acceptance bez expirace '.time();
        $I->amOnPage('/admin/oznameni/nove');
        $I->waitForElementVisible('[data-test="admin-announcements-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('input[name="title"]', $title);
        $I->fillField('textarea[name="message"]', 'Zpráva bez data konce.');
        $I->selectOption('select[name="category"]', 'info');
        $I->clickStable('[data-test="announcement-form-submit"]');
        $I->waitForText($title, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="admin-announcements-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('announcement', ['title' => $title, 'expires_at' => null]);

        $id = (int) $I->grabFromDatabase('announcement', 'id', ['title' => $title]);
        $I->amOnPage('/admin/oznameni/'.$id.'/upravit');
        $I->waitForElementVisible('[data-test="admin-announcements-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d\TH:i');
        $I->fillFieldStable('input[name="expiresAt"]', $expiresAt);
        $I->seeInField('input[name="expiresAt"]', $expiresAt);
        $I->clickStable('[data-test="announcement-form-submit"]');
        $I->waitForJS(
            "return document.querySelector('[data-id=\"{$id}\"] .badge')?.innerText === 'Zobrazeno';",
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );
        $I->seeInDatabase('announcement', [
            'id' => $id,
            'expires_at' => str_replace('T', ' ', $expiresAt).':00',
        ]);

        $I->amOnPage('/admin/oznameni/'.$id.'/upravit');
        $I->waitForElementVisible('[data-test="admin-announcements-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillFieldStable('input[name="expiresAt"]', '');
        $I->seeInField('input[name="expiresAt"]', '');
        $I->clickStable('[data-test="announcement-form-submit"]');
        $I->waitForJS(
            "return document.querySelector('[data-id=\"{$id}\"]')?.innerText.includes('Zobrazeno bez expirace') === true;",
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );
        $I->seeInDatabase('announcement', ['id' => $id, 'expires_at' => null]);

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see($title, '[data-test="dashboard-announcements"]');

        $I->deleteFromDatabase('announcement', ['id' => $id]);
    }

    private function becomeAdmin(): void
    {
        $this->I->deleteFromDatabase('system_user_role', ['user_id' => self::ADMIN_USER_ID]);
        $this->I->haveInDatabase('system_user_role', [
            'user_id' => self::ADMIN_USER_ID,
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function announcementRowActionSelector(int $id, string $action): string
    {
        return sprintf(
            '[data-id="%1$d"] [data-test="%2$s"], [data-id="%1$d-actions"] [data-test="%2$s"]',
            $id,
            $action,
        );
    }
}
