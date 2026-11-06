<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;

use function sprintf;
use function uniqid;

/**
 * Nette runs signals and form submissions after the action, so an access check that only changes
 * the view lets them run anyway. These scenarios send signals for data of a unit the acceptance user
 * cannot access and check that nothing changed. Each one also runs the same signal on the user's own
 * data, so the scenario cannot pass just because the signal did not run at all.
 */
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class AccessControlCest extends PaymentAcceptanceCest
{
    /** A unit the acceptance user has no access to. */
    private const FOREIGN_UNIT_ID = 1;

    /** @group payment */
    public function signalsOfForeignPaymentGroupAreDenied(): void
    {
        $I = $this->I;
        $foreignGroupName = uniqid('Selenium foreign group ', true);
        $foreignGroupId = $this->haveGroup(self::FOREIGN_UNIT_ID, $foreignGroupName);
        $foreignPaymentId = $this->havePreparingPayment($foreignGroupId);

        $I->wantTo('not change a payment group of a foreign unit through signals');

        foreach (['closeGroup', 'generateVs'] as $signal) {
            $I->amOnPage(sprintf('/platby/skupiny/%d/platby?do=%s', $foreignGroupId, $signal));
            $I->waitForDocumentReady();
            $I->dontSeeElement('[data-test="payment-group-detail-page"]');
            // The 403 page says why, not just that access was denied.
            $I->see('Nemáte oprávnění pracovat s touto platební skupinou.', '[data-test="error-403-reason"]');
        }

        // The panel of e-mails sent in the background has its own address, with the same rights as the group.
        foreach (['', '?do=taskQueueStatus-cancel&jobId=1'] as $query) {
            $I->amOnPage(sprintf('/platby/skupiny/%d/odesilani%s', $foreignGroupId, $query));
            $I->waitForDocumentReady();
            $I->see('Nemáte oprávnění pracovat s touto platební skupinou.', '[data-test="error-403-reason"]');
            $I->dontSeeElement('[data-test="task-queue-status"]');
        }

        $I->amOnPage(sprintf('/platby/skupiny/%d/vratky', $foreignGroupId));
        $I->waitForDocumentReady();
        $I->dontSee($foreignGroupName);

        $I->seeInDatabase('pa_group', ['id' => $foreignGroupId, 'state' => 'open']);
        $I->seeInDatabase('pa_payment', ['id' => $foreignPaymentId, 'variable_symbol' => null]);

        $ownGroupId = $this->haveGroup(AcceptanceTester::UNIT_ID, uniqid('Selenium own group ', true));
        $ownPaymentId = $this->havePreparingPayment($ownGroupId);

        $this->openPageAndWaitForElementWithSkautisRetry(
            $I,
            sprintf('/platby/skupiny/%d/platby?do=generateVs', $ownGroupId),
            '[data-test="payment-group-detail-page"]',
        );
        $I->dontSeeInDatabase('pa_payment', ['id' => $ownPaymentId, 'variable_symbol' => null]);
    }

    /** @group payment */
    public function paymentOfForeignGroupCannotBeCancelledFromOwnGroup(): void
    {
        $I = $this->I;
        $foreignPaymentId = $this->havePreparingPayment($this->haveGroup(self::FOREIGN_UNIT_ID, uniqid('Selenium foreign group ', true)));
        $ownGroupId = $this->haveGroup(AcceptanceTester::UNIT_ID, uniqid('Selenium own group ', true));
        $ownPaymentId = $this->havePreparingPayment($ownGroupId);

        $I->wantTo('not cancel a payment of a foreign group through the payment list of my group');

        $this->openPageAndWaitForElementWithSkautisRetry(
            $I,
            sprintf('/platby/skupiny/%d/platby?do=paymentList-cancel&paymentList-pid=%d', $ownGroupId, $foreignPaymentId),
            '[data-test="payment-group-detail-page"]',
        );
        $I->seeInDatabase('pa_payment', ['id' => $foreignPaymentId, 'state' => 'preparing']);

        $this->openPageAndWaitForElementWithSkautisRetry(
            $I,
            sprintf('/platby/skupiny/%d/platby?do=paymentList-cancel&paymentList-pid=%d', $ownGroupId, $ownPaymentId),
            '[data-test="payment-group-detail-page"]',
        );
        $I->seeInDatabase('pa_payment', ['id' => $ownPaymentId, 'state' => 'canceled']);
    }

    /** @group payment */
    public function bankAccountOfForeignUnitCannotBeRemoved(): void
    {
        $I = $this->I;
        $foreignAccountId = $this->haveBankAccount(self::FOREIGN_UNIT_ID, uniqid('Selenium foreign account ', true));
        $ownAccountId = $this->haveBankAccount(AcceptanceTester::UNIT_ID, uniqid('Selenium own account ', true));

        $I->wantTo('not remove a bank account of a foreign unit');

        $I->amOnPage(sprintf('/nastaveni/bankovni-ucty?do=remove&id=%d', $foreignAccountId));
        $I->waitForDocumentReady();
        $I->seeInDatabase('pa_bank_account', ['id' => $foreignAccountId]);

        $this->openPageAndWaitForElementWithSkautisRetry(
            $I,
            sprintf('/nastaveni/bankovni-ucty?do=remove&id=%d', $ownAccountId),
            '[data-test="settings-bank-accounts-page"]',
        );
        $I->dontSeeInDatabase('pa_bank_account', ['id' => $ownAccountId]);
    }

    /** @group payment */
    public function googleCallbackWithoutMatchingStateIsRejected(): void
    {
        $I = $this->I;

        $I->wantTo('not connect a Google account from a callback that this session did not start');

        $this->openPageAndWaitForElementWithSkautisRetry(
            $I,
            '/google/token?code=forged-code&state=forged-state',
            '[data-test="settings-mails-page"]',
        );
        $I->see('Google účet se nepodařilo propojit. Spusťte propojení znovu.');
    }

    private function haveGroup(int $unitId, string $name): int
    {
        $groupId = $this->I->haveInDatabase('pa_group', [
            'groupType' => null,
            'sisId' => null,
            'name' => $name,
            'amount' => 10000, // 100 Kč v haléřích
            'due_date' => ChronosDate::today()->format('Y-m-d'),
            'constant_symbol' => null,
            'next_variable_symbol' => 5000,
            'state' => 'open',
            'note' => 'acceptance',
            'created_at' => '2026-01-01 00:00:00',
            'last_pairing' => '2026-01-01 00:00:00',
            'smtp_id' => null,
            'bank_account_id' => null,
            'oauth_id' => null,
            'is_reminders_enabled' => 0,
        ]);

        $this->I->haveInDatabase('pa_group_unit', [
            'group_id' => $groupId,
            'unit_id' => $unitId,
        ]);

        return $groupId;
    }

    private function havePreparingPayment(int $groupId): int
    {
        return $this->I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba bez VS',
            'amount' => 10000, // 100 Kč v haléřích
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => null,
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
    }

    private function haveBankAccount(int $unitId, string $name): int
    {
        return $this->I->haveInDatabase('pa_bank_account', [
            'unit_id' => $unitId,
            'name' => $name,
            'token' => null,
            'transaction_source' => 'gpc',
            'created_at' => '2026-06-18 12:00:00',
            'allowed_for_subunits' => 0,
            'number_prefix' => null,
            'number_number' => '1231231230',
            'number_bank_code' => '2010',
        ]);
    }
}
