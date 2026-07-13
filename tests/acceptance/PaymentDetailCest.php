<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use PHPUnit\Framework\Assert;

use function uniqid;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentDetailCest extends PaymentAcceptanceCest
{
    /** @group payment */
    public function openPaymentGroupOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium URL ', true);

        $I->wantTo('open a payment group on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->clickStable('[data-test="payment-nav-groups"]');
        $I->waitForText('Platební skupiny', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->executeJS(
            "document.evaluate(\"//a[normalize-space(text())='$groupName']\", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();",
        );
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText($groupName, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $I->clickStable('[data-test="payment-group-edit-link"]');
        $I->waitForElementVisible('[data-test="payment-group-form-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/upravit(?:\?.*)?$~');
        $I->waitForText('Editace skupiny: '.$groupName, AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
    }

    /** @group payment */
    public function openPaymentRepaymentsOnCanonicalUrl(): void
    {
        $I = $this->I;

        $groupName = uniqid('Selenium Repayment URL ', true);

        $I->wantTo('open payment repayments on canonical url');

        $this->createGeneralPaymentGroup($groupName);

        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/platby(?:\?.*)?$~');
        $I->clickStable('[data-test="payment-group-repayments-link"]');
        $I->waitForElementVisible('[data-test="payment-repayments-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Vratky', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
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
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-add-button-item-member"]');
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Přidat osoby z jednotky', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeCurrentUrlMatches('~^/platby/skupiny/\d+/osoby(?:\?.*)?$~');
    }

    /** @group payment */
    public function massAddSelectsEmailsByContactType(): void
    {
        $I = $this->I;

        $this->createGeneralPaymentGroup(uniqid('Selenium MassAdd emails ', true));
        $I->seeElement('[data-test="payment-group-detail-page"] .page-heading .badge.text-bg-success');
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-add-button-item-member"]');
        $I->waitForElementVisible('[data-test="payment-mass-add-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        foreach (['main', 'other', 'father', 'mother'] as $emailType) {
            $I->seeElement('[data-test="mass-email-type-'.$emailType.'"]');
        }

        $summary = $I->executeJS(<<<'JS'
            const options = Array.from(document.querySelectorAll('[data-mass-email-select] option'));
            return {
                selectCount: document.querySelectorAll('[data-mass-email-select]').length,
                mainCount: options.filter(option => option.dataset.emailType === 'main').length,
                otherCount: options.filter(option => option.dataset.emailType === 'other').length,
                unselectedMainCount: options.filter(option => option.dataset.emailType === 'main' && !option.selected).length,
                selectedNonMainCount: options.filter(option => option.dataset.emailType !== 'main' && option.selected).length,
                availableBulkTypes: ['other', 'father', 'mother'].filter(
                    type => options.some(option => option.dataset.emailType === type),
                ),
            };
            JS);

        Assert::assertGreaterThan(0, $summary['selectCount']);
        Assert::assertGreaterThan(0, $summary['mainCount']);
        Assert::assertGreaterThan(0, $summary['otherCount']);
        Assert::assertSame(0, $summary['unselectedMainCount']);
        Assert::assertSame(0, $summary['selectedNonMainCount']);
        Assert::assertNotEmpty($summary['availableBulkTypes']);

        $emailType = $summary['availableBulkTypes'][0];
        $toggle = '[data-test="mass-email-type-'.$emailType.'"]';
        $I->click($toggle);
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\'))'
            .'.every(option => option.selected);',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        $I->click($toggle);
        $I->waitForJS(
            'return Array.from(document.querySelectorAll(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\'))'
            .'.every(option => !option.selected);',
            AcceptanceTester::ELEMENT_LOAD_TIMEOUT,
        );

        $manuallySelected = $I->executeJS(
            'const option = document.querySelector(\'[data-mass-email-select] option[data-email-type="'.$emailType.'"]\');'
            .'option.selected = true;'
            .'option.parentElement.dispatchEvent(new Event("change", {bubbles: true}));'
            .'return option.selected;',
        );

        Assert::assertTrue($manuallySelected);
        $I->dontSeeCheckboxIsChecked($toggle);
    }

    /** @group payment */
    /** @group payment */
    public function participantAddLinkUsesCanonicalUrlForSubtypeGroups(): void
    {
        $I = $this->I;

        $I->wantTo('see canonical participant add link for camp, event and education payment groups');

        foreach (['camp', 'event', 'education'] as $type) {
            $groupId = $this->createSubtypePaymentGroup($type);

            $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
            $I->waitForText('Přidat platbu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
            $I->clickStable('[data-test="payment-add-button-toggle"]');
            $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
        $I->waitForText('Přidat platbu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-add-button-toggle"]');
        $I->waitForElementVisible('[data-test="payment-add-button-menu"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

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
        $I->waitForText('Přidat platbu', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        Assert::assertSame(
            '/platby/skupiny/'.$groupId.'/casopisy',
            $I->grabAttributeFrom('[data-test="payment-group-journal-link"]', 'href'),
        );
    }

    /** @group payment */
    public function paymentEmailActionIsDisabledWithoutSender(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $paymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba bez odesílatele',
            'amount' => 500,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '900001',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
        $I->haveInDatabase('pa_payment_email_recipients', [
            'payment_id' => $paymentId,
            'email_address' => 'recipient@example.com',
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $selector = '[data-test="payment-email-action-'.$paymentId.'"]';
        $I->seeElement($selector.'.btn-light.disabled');
        $I->seeElement($selector.'[title="Skupina nemá nastavený e-mail odesílatele"]');
        $I->dontSeeElement($selector.'.ui--sendEmail');
    }

    /** @group payment */
    public function paymentGridRendersRowWithTextNote(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $paymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba s textovou poznámkou',
            'amount' => 500,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '900002',
            'constant_symbol' => null,
            'note' => 'Textová poznámka v gridu',
            'state' => 'preparing',
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-grid"] .datagrid', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Platba s textovou poznámkou', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-group-grid"]');
        $I->seeElement('[data-test="payment-group-grid"] [title="Textová poznámka v gridu"]');
        $I->seeElement('[data-test="payment-split-action-'.$paymentId.'"]');
        $I->seeElement('[data-test="payment-email-action-'.$paymentId.'"]');
    }

    /** @group payment */
    public function splitPaymentIntoMultiplePayments(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $sourcePaymentId = $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Dělená účastnická platba',
            'person_id' => 987,
            'amount' => 1000,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '100100',
            'constant_symbol' => 308,
            'note' => 'Platba účastníka',
            'state' => 'preparing',
        ]);
        $I->haveInDatabase('pa_payment_email_recipients', [
            'payment_id' => $sourcePaymentId,
            'email_address' => 'participant@example.com',
        ]);
        $I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Jiná platba ve skupině',
            'amount' => 100,
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => '100103',
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);

        $I->amOnPage('/platby/skupiny/'.$groupId.'/platby');
        $I->waitForElementVisible('[data-test="payment-group-detail-page"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->clickStable('[data-test="payment-split-action-'.$sourcePaymentId.'"]');
        $I->waitForElementVisible('[data-test="payment-split-form"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeElement('[data-test="payment-split-infobar"]');
        $I->seeNumberOfElements('[data-test="payment-split-row"]', 1);

        $I->clickStable('.modal-footer [data-test="payment-split-add"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->waitForJS('return document.querySelectorAll(\'[data-test="payment-split-row"]\').length === 2;', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100101', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-amount', '300', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-note', 'Faktura zaměstnavatele', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-variableSymbol', '100101', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-amount', '200', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $I->waitForElementVisible('[data-test="payment-split-errors"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Každá nová platba musí mít jiný variabilní symbol.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-split-errors"]');
        $I->seeInDatabase('pa_payment', ['id' => $sourcePaymentId, 'amount' => 1000]);
        $I->dontSeeInDatabase('pa_payment', ['split_from_payment_id' => $sourcePaymentId]);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100103', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-1-variableSymbol', '100102', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $I->waitForElementVisible('[data-test="payment-split-errors"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForText('Variabilní symbol 100103 je už použitý v této platební skupině.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="payment-split-errors"]');
        $I->seeInDatabase('pa_payment', ['id' => $sourcePaymentId, 'amount' => 1000]);
        $I->dontSeeInDatabase('pa_payment', ['split_from_payment_id' => $sourcePaymentId]);

        $I->fillFieldStable('#frm-splitPaymentDialog-form-splits-0-variableSymbol', '100101', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);
        $I->clickStable('.modal-footer [data-test="payment-split-submit"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, false);

        $I->waitForText('Platba byla rozdělena na více plateb.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementNotVisible('.modal-backdrop', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->seeInDatabase('pa_payment', [
            'id' => $sourcePaymentId,
            'amount' => 500,
        ]);

        foreach ([['100101', 300, 'Faktura zaměstnavatele'], ['100102', 200, 'Platba účastníka']] as [$variableSymbol, $amount, $note]) {
            $I->seeInDatabase('pa_payment', [
                'group_id' => $groupId,
                'name' => 'Dělená účastnická platba',
                'person_id' => 987,
                'amount' => $amount,
                'variable_symbol' => $variableSymbol,
                'constant_symbol' => 308,
                'note' => $note,
                'state' => 'preparing',
                'split_from_payment_id' => $sourcePaymentId,
            ]);
        }

        $splitPaymentIds = $I->grabColumnFromDatabase('pa_payment', 'id', [
            'split_from_payment_id' => $sourcePaymentId,
        ]);
        Assert::assertCount(2, $splitPaymentIds);
        foreach ($splitPaymentIds as $splitPaymentId) {
            $I->seeInDatabase('pa_payment_email_recipients', [
                'payment_id' => $splitPaymentId,
                'email_address' => 'participant@example.com',
            ]);
        }

        $I->seeNumberOfElements('//*[contains(text(), "Rozděleno z platby #'.$sourcePaymentId.'")]', 2);
    }
}
