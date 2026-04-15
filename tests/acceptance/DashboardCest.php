<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

class DashboardCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group dashboard */
    public function seeAgendaCardsAndPaymentQuickActions(): void
    {
        $I = $this->I;

        $I->wantTo('see dashboard agenda cards and payment quick actions');

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', 10);
        $I->seeNumberOfElements('[data-test^="dashboard-card-"]', 6);
        $I->seeElement('[data-test="dashboard-card-events"]');
        Assert::assertSame(
            '/akce',
            $I->grabAttributeFrom('[data-test="dashboard-link-events"]', 'href'),
        );
        $I->seeElement('[data-test="dashboard-card-camps"]');
        Assert::assertSame(
            '/tabory',
            $I->grabAttributeFrom('[data-test="dashboard-link-camps"]', 'href'),
        );
        $I->seeElement('[data-test="dashboard-card-education"]');
        Assert::assertSame(
            '/vzdelavacky',
            $I->grabAttributeFrom('[data-test="dashboard-link-education"]', 'href'),
        );
        $I->seeElement('[data-test="dashboard-card-travel"]');
        Assert::assertSame(
            '/cestaky',
            $I->grabAttributeFrom('[data-test="dashboard-link-travel"]', 'href'),
        );
        $I->seeElement('[data-test="dashboard-card-unit"]');
        Assert::assertSame(
            '/jednotka',
            $I->grabAttributeFrom('[data-test="dashboard-link-unit"]', 'href'),
        );
        $I->seeElement('[data-test="dashboard-card-payments"]');
        $I->seeElement('[data-test="dashboard-link-payments"]');
        $I->seeElement('[data-test="dashboard-payment-actions"] [data-test="create-button-main"]');
        $I->seeElement('[data-test="dashboard-payment-actions"] [data-test="pair-button-main"]');
        $I->click('[data-test="dashboard-payment-actions"] [data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="dashboard-payment-actions"] [data-test="create-button-menu"]', 10);
        $I->seeElement('[data-test="create-button-item-general"]');
        $I->seeElement('[data-test="create-button-item-camp"]');
        Assert::assertSame(
            '/platby/tabory',
            $I->grabAttributeFrom('[data-test="dashboard-payment-actions"] [data-test="create-button-item-camp"]', 'href'),
        );
        $I->seeElement('[data-test="create-button-item-event"]');
        Assert::assertSame(
            '/platby/akce',
            $I->grabAttributeFrom('[data-test="dashboard-payment-actions"] [data-test="create-button-item-event"]', 'href'),
        );
        $I->seeElement('[data-test="create-button-item-registration"]');
        Assert::assertSame(
            '/platby/registrace/nova',
            $I->grabAttributeFrom('[data-test="dashboard-payment-actions"] [data-test="create-button-item-registration"]', 'href'),
        );
        $I->seeElement('[data-test="create-button-item-education"]');
        Assert::assertSame(
            '/platby/vzdelavacky',
            $I->grabAttributeFrom('[data-test="dashboard-payment-actions"] [data-test="create-button-item-education"]', 'href'),
        );

        $I->click('[data-test="dashboard-payment-actions"] [data-test="create-button-main"]');
        $I->waitForElementVisible('[data-test="payment-group-form-page"]', 10);
        $I->seeInCurrentUrl('/platby/skupiny/nova');

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', 10);
        $I->click('[data-test="dashboard-link-events"]');
        $I->waitForElementVisible('[data-test="events-default-page"]', 10);
        $I->seeInCurrentUrl('/akce');
        $I->seeElement('.active [data-test="global-nav-events"]');

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', 10);
        $I->click('[data-test="dashboard-link-camps"]');
        $I->waitForElementVisible('[data-test="camps-default-page"]', 10);
        $I->seeInCurrentUrl('/tabory');
        $I->seeElement('.active [data-test="global-nav-camps"]');

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', 10);
        $I->click('[data-test="dashboard-link-education"]');
        $I->waitForElementVisible('[data-test="education-default-page"]', 10);
        $I->seeInCurrentUrl('/vzdelavacky');
        $I->seeElement('.active [data-test="global-nav-education"]');

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', 10);
        $I->click('[data-test="dashboard-link-travel"]');
        $I->waitForElementVisible('[data-test="travel-default-page"]', 10);
        $I->seeInCurrentUrl('/cestaky');
        $I->seeElement('.active [data-test="global-nav-travel"]');

        $I->amOnPage('/nastenka');
        $I->waitForElementVisible('[data-test="dashboard"]', 10);
        $I->click('[data-test="dashboard-link-unit"]');
        $I->waitForElementVisible('[data-test="unit-cashbook-page"]', 10);
        $I->seeInCurrentUrl('/jednotka');
        $I->seeElement('.active [data-test="global-nav-unit"]');
    }
}
