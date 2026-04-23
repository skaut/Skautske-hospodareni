<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use Page\Payment;
use PHPUnit\Framework\Assert;

use function str_contains;
use function uniqid;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;
    protected Payment $page;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $this->page = new Payment($I);
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    /** @group payment */
    public function createPaymentGroup(): void
    {
        $I = $this->I;
        $page = $this->page;

        $I->wantTo('create payment group');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná');
        $I->seeInCurrentUrl('/platby/skupiny/nova');
        $I->fillField('Název', 'Jaráky');
        $I->click('//option[text()="Vyberte e-mail"]');
        $I->click('//option[text()="test@hospodareni.loc"]');

        $I->click('//option[text()="Vyberte bankovní účet"]');
        $I->click('//option[text()="Acceptance"]');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]');
        $I->executeJS('document.querySelector(\'input[name="send"]\').click()');

        $I->waitForText('Skupina byla založena', 15);

        $I->wantTo('create payments');

        $I->amGoingTo('add first payment');
        $page->addPayment('Testovací platba 1', null, 500);

        $I->amGoingTo('add second payment');
        $page->addPayment('Testovací platba 2', null, 500);

        $I->amGoingTo('add third payment');
        $page->addPayment('Testovací platba 3', 'frantisekmasa1@gmail.com', 300);

        $I->wantTo('complete payment');

        $I->amGoingTo('mark second payment as complete');
        $I->waitForJS(
            'return document.querySelectorAll(\'a[title="Zaplaceno"]\').length >= 2;',
            10,
        );
        $I->executeJS(
            'var links = Array.from(document.querySelectorAll(\'a[title="Zaplaceno"]\'))'
            .'.filter(function (link) { return link.offsetParent !== null; });'
            .'if (links.length < 2) { return false; }'
            .'links[1].click();'
            .'return true;',
        );
        $I->waitForText('Dokončena', 10);

        $I->canSeeNumberOfElements('(//*[text()="Nezaplacena"])', 2);
        $I->see('Dokončena');

        $I->wantTo('send payment email');

        $I->amGoingTo('send third payment');
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'a[title="Odeslat e-mail o platbě"].ui--sendEmail\'))'
            .'.some(function (link) { return link.offsetParent !== null; });',
            10,
        );
        $I->executeJS(
            'var links = Array.from(document.querySelectorAll(\'a[title="Odeslat e-mail o platbě"].ui--sendEmail\'))'
            .'.filter(function (link) { return link.offsetParent !== null; });'
            .'if (links.length === 0) { return false; }'
            .'links[links.length - 1].click();'
            .'return true;',
        );
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(".alert"))'
            .'.some(function (alert) { return alert.textContent.toLowerCase().includes("e-mail"); });',
            10,
        );

        $page->seeNumberOfPaymentsWithState('Nezaplacena', 2);
        $I->see('1 / 3 plateb'); // Progress bar: 1 paid out of 3 total

        $I->wantTo('close and reopen group');
        $I->click('Uzavřít');
        $I->waitForText('Znovu otevřít');
        $I->click('Znovu otevřít');
        $I->waitForText('Uzavřít');

        $I->amGoingTo('close group');
        $I->click('Uzavřít');
    }

    /** @group payment */
    public function registrationCreateLinkUsesCanonicalUrl(): void
    {
        $I = $this->I;

        $I->wantTo('see canonical registration create link in payment group list');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForElementVisible('[data-test="payments-groups-page"]', 10);
        $I->seeInCurrentUrl('/platby/skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);

        Assert::assertSame(
            '/platby/registrace/nova',
            $I->grabAttributeFrom('[data-test="create-button-item-registration"]', 'href'),
        );
    }

    /** @group payment */
    public function subtypeCreateLinksUseCanonicalUrls(): void
    {
        $I = $this->I;

        $I->wantTo('open payment subtype selectors on canonical urls');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);

        Assert::assertSame('/platby/tabory', $I->grabAttributeFrom('[data-test="create-button-item-camp"]', 'href'));
        Assert::assertSame('/platby/akce', $I->grabAttributeFrom('[data-test="create-button-item-event"]', 'href'));
        Assert::assertSame('/platby/vzdelavacky', $I->grabAttributeFrom('[data-test="create-button-item-education"]', 'href'));

        $I->clickStable('[data-test="create-button-item-camp"]');
        $I->waitForText('Nová táborová skupina plateb', 10);
        $I->seeInCurrentUrl('/platby/tabory');

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);
        $I->clickStable('[data-test="create-button-item-event"]');
        $I->waitForText('Nová skupina plateb pro akci', 10);
        $I->seeInCurrentUrl('/platby/akce');

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);
        $I->clickStable('[data-test="create-button-item-education"]');
        $I->waitForText('Nová skupina plateb vzdělávací akce', 10);
        $I->seeInCurrentUrl('/platby/vzdelavacky');
    }

    /** @group payment */
    public function subtypeCreateFormsUseCanonicalUrls(): void
    {
        $I = $this->I;

        $I->wantTo('open payment subtype create forms on canonical urls when selectable records exist');

        $this->openPaymentSubtypeSelector('[data-test="create-button-item-camp"]', 'Nová táborová skupina plateb', '/platby/tabory');
        if (str_contains($I->grabPageSource(), 'data-test="payment-camp-create-')) {
            Assert::assertMatchesRegularExpression(
                '~^/platby/tabory/\d+/nova(?:\?.*)?$~',
                (string) $I->grabAttributeFrom('[data-test^="payment-camp-create-"]', 'href'),
            );
            $I->click('[data-test^="payment-camp-create-"]');
            $I->waitForText('Nová skupina plateb', 10);
            $I->seeCurrentUrlMatches('~^/platby/tabory/\d+/nova(?:\?.*)?$~');
        }

        $this->openPaymentSubtypeSelector('[data-test="create-button-item-event"]', 'Nová skupina plateb pro akci', '/platby/akce');
        if (str_contains($I->grabPageSource(), 'data-test="payment-event-create-')) {
            $eventHref = (string) $I->grabAttributeFrom('[data-test^="payment-event-create-"]', 'href');

            if ($eventHref !== '#' && $eventHref !== '') {
                Assert::assertMatchesRegularExpression(
                    '~^/platby/akce/\d+/nova(?:\?.*)?$~',
                    $eventHref,
                );
                $I->clickStable('[data-test^="payment-event-create-"]');
                $I->waitForText('Nová skupina plateb', 10);
                $I->seeCurrentUrlMatches('~^/platby/akce/\d+/nova(?:\?.*)?$~');
            } else {
                $I->comment('Event create link has no valid href — skipping');
            }
        } else {
            $I->comment('No events available for payment group creation — skipping event subtype check');
        }

        $this->openPaymentSubtypeSelector('[data-test="create-button-item-education"]', 'Nová skupina plateb vzdělávací akce', '/platby/vzdelavacky');
        if (str_contains($I->grabPageSource(), 'data-test="payment-education-create-')) {
            Assert::assertMatchesRegularExpression(
                '~^/platby/vzdelavacky/\d+/nova(?:\?.*)?$~',
                (string) $I->grabAttributeFrom('[data-test^="payment-education-create-"]', 'href'),
            );
            $I->click('[data-test^="payment-education-create-"]');
            $I->waitForText('Nová skupina plateb', 10);
            $I->seeCurrentUrlMatches('~^/platby/vzdelavacky/\d+/nova(?:\?.*)?$~');
        }
    }

    /** @group payment */
    public function openPaymentGroupOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium URL ', true);

        $I->wantTo('open a payment group on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);

        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->executeJS(
            "document.evaluate(\"//a[normalize-space(text())='$groupName']\", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();",
        );
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
        $I->waitForText($groupName);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
    }

    /** @group payment */
    public function openPaymentGroupEditOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Edit URL ', true);

        $I->wantTo('open payment group edit on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->click('[data-test="payment-group-edit-link"]');
        $I->waitForElementVisible('[data-test="payment-group-form-page"]', 10);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/upravit(?:\?.*)?$~');
        $I->waitForText('Editace skupiny: '.$groupName);
    }

    /** @group payment */
    public function openPaymentRepaymentsOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Repayment URL ', true);

        $I->wantTo('open payment repayments on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->click('[data-test="payment-group-repayments-link"]');
        $I->waitForElementVisible('[data-test="payment-repayments-page"]', 10);
        $I->waitForText('Vratky');
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/vratky(?:\?.*)?$~');
    }

    /** @group payment */
    public function openPaymentMassAddOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium MassAdd URL ', true);

        $I->wantTo('open payment mass add on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);
        $I->clickStable('[data-test="payment-add-button-item-member"]');
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', 10);
        $I->waitForText('Přidat osoby z jednotky');
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/osoby(?:\?.*)?$~');
    }

    /** @group payment */
    public function participantAddLinkUsesCanonicalUrlForSubtypeGroups(): void
    {
        $I = $this->I;

        $I->wantTo('see canonical participant add link for camp, event and education payment groups');

        foreach (['camp', 'event', 'education'] as $type) {
            $groupId = $this->createSubtypePaymentGroup($type);

            $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
            $I->waitForText('Přidat platbu');
            $I->clickStable('[data-test="payment-add-button-toggle"]');
            $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);

            Assert::assertSame(
                '/platby/skupiny/'.$groupId.'/ucastnici',
                $I->grabAttributeFrom('[data-test="payment-add-button-item-participant"]', 'href'),
            );
        }
    }

    /** @group payment */
    public function registrationAddLinkUsesCanonicalGroupUrl(): void
    {
        $I = $this->I;

        $I->wantTo('see group-centric registration add link in payment group detail');

        $groupId = $this->createSubtypePaymentGroup('registration');

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForText('Přidat platbu');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', 10);

        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/osoby',
            $I->grabAttributeFrom('[data-test="payment-add-button-item-registration"]', 'href'),
        );
    }

    /** @group payment */
    public function registrationJournalUsesCanonicalGroupUrl(): void
    {
        $I = $this->I;

        $I->wantTo('see a group-centric canonical journal link in registration payment group detail');

        $groupId = $this->createSubtypePaymentGroup('registration');

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForText('Přidat platbu');

        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/casopisy',
            $I->grabAttributeFrom('[data-test="payment-group-journal-link"]', 'href'),
        );
    }

    private function openPaymentSubtypeSelector(string $menuItemSelector, string $pageTitle, string $currentUrl): void
    {
        $I = $this->I;

        $I->clickStable('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->clickStable('[data-test="create-button-toggle"]');
        $I->waitForElementVisible('[data-test="create-button-menu"]', 10);
        $I->clickStable($menuItemSelector);
        $I->waitForText($pageTitle, 10);
        $I->seeInCurrentUrl($currentUrl);
    }

    private function createGeneralPaymentGroup(string $groupName): void
    {
        $I = $this->I;

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná');
        $I->seeInCurrentUrl('/platby/skupiny/nova');
        $I->fillField('Název', $groupName);
        $I->click('//option[text()="Vyberte e-mail"]');
        $I->click('//option[text()="test@hospodareni.loc"]');
        $I->click('//option[text()="Vyberte bankovní účet"]');
        $I->click('//option[text()="Acceptance"]');
        $I->scrollTo('input[name="send"]');
        $I->waitForElementClickable('input[name="send"]');
        $I->executeJS('document.querySelector(\'input[name="send"]\').click()');
        $I->waitForText('Skupina byla založena', 15);
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', 10);
    }

    private function createSubtypePaymentGroup(string $type): int
    {
        $I = $this->I;

        $groupId = $I->haveInDatabase('pa_group', [
            'groupType' => $type,
            'sisId' => 1000 + random_int(1, 100000),
            'name' => uniqid('Payment subtype '.$type.' ', true),
            'amount' => 100.0,
            'due_date' => ChronosDate::today()->format('Y-m-d'),
            'constant_symbol' => null,
            'next_variable_symbol' => null,
            'state' => 'open',
            'note' => 'acceptance',
            'created_at' => '2026-01-01 00:00:00',
            'last_pairing' => '2026-01-01 00:00:00',
            'smtp_id' => null,
            'bank_account_id' => 1,
            'oauth_id' => null,
            'is_reminders_enabled' => 0,
        ]);

        $I->haveInDatabase('pa_group_unit', [
            'group_id' => $groupId,
            'unit_id' => AcceptanceTester::UNIT_ID,
        ]);

        $I->haveInDatabase('pa_group_email', [
            'group_id' => $groupId,
            'type' => 'payment_info',
            'template_subject' => 'Acceptance',
            'template_body' => 'Acceptance body',
            'enabled' => 1,
        ]);

        return $groupId;
    }

    /** @group settings */
    public function openSystemSettingsFromPaymentSubmenu(): void
    {
        $I = $this->I;

        $I->wantTo('open system settings from the payment submenu');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->seeInCurrentUrl('/platby');
        $I->seeElement('[data-test="payment-nav-overview"]');
        $I->seeElement('[data-test="payments-card-groups"]');
        Assert::assertSame('/platby/skupiny', $I->grabAttributeFrom('[data-test="payments-link-groups"]', 'href'));
        $I->seeElement('[data-test="payments-card-invoices"]');
        $I->seeElement('[data-test="payments-card-settings"]');
        $I->waitForElementVisible('[data-test="payment-nav-settings"]', 10);
        $I->click('[data-test="payment-nav-settings"]');

        $I->waitForText('Nastavení', 10);
        $I->seeInCurrentUrl('/nastaveni');
    }

    // ─── Help Panel Toggle ──────────────────────────────────────

    /** @group payment */
    public function helpPanelToggleOnGroupCreate(): void
    {
        $I = $this->I;

        $I->wantTo('toggle help panel on payment group create page');

        $I->click('[data-test="global-nav-payments"]');
        $I->waitForElementVisible('[data-test="payments-page"]', 10);
        $I->click('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny');
        $I->click('Založit skupinu plateb');
        $I->waitForText('Nová platební skupina - Obecná');

        // Sidebar and content visible initially
        $I->seeElement('[data-test="help-sidebar"]');
        $I->seeElement('[data-test="help-content"]');
        $I->seeElement('[data-test="help-toggle"]');

        // Collapse
        $I->click('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "true"', 5);

        $collapsed = $I->executeJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed');
        Assert::assertSame('true', $collapsed);

        // Expand
        $I->click('[data-test="help-toggle"]');
        $I->waitForJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed === "false"', 5);

        $expanded = $I->executeJS('return document.querySelector("[data-help-layout]")?.dataset.helpCollapsed');
        Assert::assertSame('false', $expanded);
    }
}
