<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Cake\Chronos\ChronosDate;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;

use function sprintf;
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

    /**
     * A sender Google refuses: the dashboard and the group tell the user, offer to connect it again and
     * nothing new gets into the queue.
     *
     * @group payment
     */
    public function refusedSenderIsShownAndBlocksSending(): void
    {
        $I = $this->I;
        $email = uniqid('refused-', false).'@hospodareni.loc';
        $oAuthId = $this->haveRefusedSender($email);
        $groupId = $this->createSubtypePaymentGroup('event');
        $I->updateInDatabase('pa_group', ['oauth_id' => $oAuthId], ['id' => $groupId]);
        $this->havePaymentWithEmail($groupId, '910003');

        $I->wantTo('learn that the sender of my group stopped working and how to fix it');

        // The dashboard shows it first, before the announcements, with a button straight to Google.
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/nastenka', '[data-test="dashboard-broken-sender"]');
        $I->see('Propojení e-mailu '.$email.' přestalo platit', '[data-test="dashboard-broken-sender"]');
        $I->seeElement('[data-test="dashboard-announcements"] article:first-of-type[data-test="dashboard-broken-sender"]');
        $I->seeElement('[data-test="dashboard-broken-sender"] .dashboard-announcement-category.text-danger');
        $reconnectUrl = (string) $I->grabAttributeFrom('[data-test="dashboard-broken-sender-reconnect"]', 'href');
        Assert::assertStringContainsString('/google/oauth', $reconnectUrl);
        Assert::assertStringContainsString('email=', $reconnectUrl);

        // The mail settings mark the account, connecting it again there clears the mark.
        $this->openPageAndWaitForElementWithSkautisRetry($I, '/nastaveni/emaily', '[data-test="settings-mail-invalid-'.$oAuthId.'"]');
        $I->see('Propojení neplatí', '[data-test="settings-mail-invalid-'.$oAuthId.'"]');
        $I->see('Klikněte nahoře na „Propojit účet“', '[data-test="settings-mail-invalid-help-'.$oAuthId.'"]');

        // The group shows it too, and sending gets nothing into the queue.
        $this->openPageAndWaitForElementWithSkautisRetry($I, sprintf('/platby/skupiny/%d/platby', $groupId), '[data-test="payment-group-broken-sender"]');
        $I->see($email, '[data-test="payment-group-broken-sender"]');
        $I->seeElement('[data-test="payment-group-broken-sender-reconnect"]');
        $I->clickStable('[data-test="payment-group-email-toggle"]');
        $I->clickStable('[data-test="payment-group-email-send-all"]');

        $I->waitForText('E-maily nejde odeslat: propojení účtu '.$email.' s Gmailem přestalo platit.', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->dontSeeInDatabase('task_queue_job', ['scope_type' => 'payment_group', 'scope_id' => $groupId]);
    }

    /** @group payment */
    public function failedEmailsShowTheirReasonOnce(): void
    {
        $I = $this->I;
        $groupId = $this->createSubtypePaymentGroup('event');
        $reason = 'Propojení účtu test@hospodareni.loc s Gmailem přestalo platit.';
        $jobId = $I->haveInDatabase('task_queue_job', [
            'type' => 'payment_email',
            'scope_type' => 'payment_group',
            'scope_id' => $groupId,
            'label' => 'Platební údaje',
            'created_at' => ChronosDate::today()->format('Y-m-d H:i:s'),
        ]);
        foreach (['Platba A', 'Platba B'] as $label) {
            $I->haveInDatabase('task_queue_item', [
                'job_id' => $jobId,
                'state' => 'failed',
                'payload' => '{}',
                'label' => $label,
                'attempts' => 1,
                'available_at' => ChronosDate::today()->format('Y-m-d H:i:s'),
                'finished_at' => ChronosDate::today()->format('Y-m-d H:i:s'),
                'message' => $reason,
            ]);
        }

        $I->wantTo('see e-mails that did not go out clearly, with the reason only once');

        $this->openPageAndWaitForElementWithSkautisRetry($I, sprintf('/platby/skupiny/%d/platby', $groupId), '[data-test="task-queue-job-failed"]');
        $I->seeElement('[data-test="task-queue-job-failed"].alert-danger');
        $I->see('Nepodařilo se odeslat 2 e-maily.', '[data-test="task-queue-job-failed"]');
        $I->seeNumberOfElements('[data-test="task-queue-job-failed"] p', 1);
        $I->seeNumberOfElements('[data-test="task-queue-job-failed"] li', 2);
        // The batch is finished, so its bar is full; it must not look like a success.
        $I->seeElement(sprintf('[data-test="task-queue-job-%d"] .progress-bar.bg-danger', $jobId));
        $I->see($reason, '[data-test="task-queue-job-failed"] p');
    }

    /** A connected account of the unit that Google refuses; removed with the other rows of the scenario. */
    private function haveRefusedSender(string $email): string
    {
        $oAuthId = Uuid::uuid4()->toString();
        $this->I->haveInDatabase('google_oauth', [
            'id' => $oAuthId,
            'unit_id' => AcceptanceTester::UNIT_ID,
            'email' => $email,
            'token' => 'refused-token',
            'updated_at' => '2026-01-01 00:00:00',
            'invalid_since' => '2026-11-09 10:00:00',
            'invalid_reason' => 'invalid_grant => Bad Request',
        ]);

        return $oAuthId;
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
