<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;

use function uniqid;

/**
 * More e-mails at once wait in the task queue; the group page shows their progress and refreshes only the panel.
 */
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class PaymentEmailQueueCest extends PaymentAcceptanceCest
{
    /** @group payment */
    public function bulkEmailsWaitInQueueAndPanelShowsTheirProgress(): void
    {
        $I = $this->I;
        $groupName = uniqid('Selenium queue ', true);
        $this->createGeneralPaymentGroup($groupName);
        $groupId = (int) $I->grabFromDatabase('pa_group', 'id', ['name' => $groupName]);
        $paymentIds = [$this->havePaymentWithEmail($groupId, '910001'), $this->havePaymentWithEmail($groupId, '910002')];

        $I->wantTo('send payment e-mails of a group through the background queue');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/platby/skupiny/'.$groupId.'/platby', '[data-test="payment-group-detail-page"]');
        $I->clickStable('[data-test="payment-group-email-toggle"]');
        $I->clickStable('[data-test="payment-group-email-send-all"]');

        $I->waitForText('2 e-maily byly zařazeny k odeslání.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->waitForElementVisible('[data-test="task-queue-status-panel"]', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->see('odesláno 0 z 2', '[data-test="task-queue-job-progress"]');
        $I->dontSeeInDatabase('pa_payment_sent_emails', ['payment_id' => $paymentIds[0]]);

        // The cron runs the queue; the page refreshes only the panel, without loading the page again.
        $I->executeJS('document.body.dataset.pageMarker = "kept"');
        $I->runCronJob('taskQueue');

        $I->waitForText('odesláno 2 z 2', AcceptanceTester::ELEMENT_LOAD_TIMEOUT, '[data-test="task-queue-job-progress"]');
        $I->seeElement('[data-test="task-queue-finished-action"]');
        $I->seeElement('body[data-page-marker="kept"]');
        $I->seeInDatabase('pa_payment_sent_emails', ['payment_id' => $paymentIds[0], 'type' => 'payment_info']);
        $I->seeInDatabase('pa_payment_sent_emails', ['payment_id' => $paymentIds[1], 'type' => 'payment_info']);
    }

    /** @group payment */
    public function groupHeaderShowsAutomaticReminders(): void
    {
        $I = $this->I;
        $groupName = uniqid('Selenium reminders ', true);
        $this->createGeneralPaymentGroup($groupName);
        $groupId = (int) $I->grabFromDatabase('pa_group', 'id', ['name' => $groupName]);
        $I->updateInDatabase('pa_group', ['is_reminders_enabled' => 1, 'reminder_days_after_due' => 3], ['id' => $groupId]);
        $I->haveInDatabase('pa_group_email', [
            'group_id' => $groupId,
            'type' => 'payment_reminder',
            'template_subject' => 'Upomínka',
            'template_body' => 'Připomínáme platbu.',
            'enabled' => 1,
        ]);
        $I->updateInDatabase('cron_job', ['paused' => 1], ['name' => 'paymentReminders']);

        $I->wantTo('see in the group header whether automatic reminders go out');

        $this->openPageAndWaitForElementWithSkautisRetry($I, '/platby/skupiny/'.$groupId.'/platby', '[data-test="payment-group-automatic-reminders"]');
        $I->see('Automatické upomínky jsou pozastavené', '[data-test="payment-group-automatic-reminders"]');

        $I->updateInDatabase('cron_job', ['paused' => 0], ['name' => 'paymentReminders']);
        try {
            $this->openPageAndWaitForElementWithSkautisRetry($I, '/platby/skupiny/'.$groupId.'/platby', '[data-test="payment-group-automatic-reminders"]');
            $I->see('Automatické upomínky 3 dny po splatnosti', '[data-test="payment-group-automatic-reminders"]');
        } finally {
            // Deployed paused, other scenarios must not see it running.
            $I->updateInDatabase('cron_job', ['paused' => 1], ['name' => 'paymentReminders']);
        }
    }

    private function havePaymentWithEmail(int $groupId, string $variableSymbol): int
    {
        $paymentId = $this->I->haveInDatabase('pa_payment', [
            'group_id' => $groupId,
            'name' => 'Platba '.$variableSymbol,
            'amount' => 10000, // 100 Kč v haléřích
            'due_date' => ChronosDate::today()->addWeekdays(1)->format('Y-m-d'),
            'variable_symbol' => $variableSymbol,
            'constant_symbol' => null,
            'note' => '',
            'state' => 'preparing',
        ]);
        $this->I->haveInDatabase('pa_payment_email_recipients', [
            'payment_id' => $paymentId,
            'email_address' => 'recipient@example.com',
        ]);

        return $paymentId;
    }
}
