<?php

declare(strict_types=1);

namespace App\Model\Payment\IntegrationTests;

use App\Model\Common\EmailAddress;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\User;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\InvalidOAuth;
use App\Model\Google\OAuthId;
use App\Model\Payment\Commands\Payment\CreatePayment;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\MailerFactoryStub;
use App\Model\Payment\Mailing\AutomaticPaymentReminders;
use App\Model\Payment\Mailing\PaymentEmailDispatch;
use App\Model\Payment\Mailing\PaymentEmailQueue;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\CountAutomaticReminderGroupsQuery;
use App\Model\Payment\RecordingMailer;
use App\Model\Payment\UserRepositoryStub;
use App\Model\TaskQueue\Entity\TaskItem;
use App\Model\TaskQueue\Entity\TaskQueueRun;
use App\Model\TaskQueue\TaskItemState;
use App\Model\TaskQueue\TaskQueueProcessor;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;

use function microtime;

/**
 * E-mails of payments go through the task queue, each at most once, recorded under the name of who
 * sent them.
 */
final class PaymentEmailQueueTest extends IntegrationTest
{
    private const EMAIL = 'test@hospodareni.loc';
    private const USER_NAME = 'František Maša';
    private const OAUTH_ID = '42288e92-27fb-453c-9904-36a7ebd14fe2';

    private PaymentService $paymentService;

    private PaymentEmailQueue $emailQueue;

    private UserRepositoryStub $users;

    private RecordingMailer $mailer;

    private int $groupId;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Group::class, Payment::class, GoogleOAuth::class, TaskItem::class, TaskQueueRun::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Payment/UseCases/PaymentCompletedEmailTest.neon']);

        parent::_before();

        $this->paymentService = $this->tester->grabService(PaymentService::class);
        $this->emailQueue = $this->tester->grabService(PaymentEmailQueue::class);
        $this->users = $this->tester->grabService(UserRepositoryStub::class);
        $this->users->setUser(new User(10, self::USER_NAME, self::EMAIL));
        $this->mailer = new RecordingMailer();
        $this->tester->grabService(MailerFactoryStub::class)->setMailer($this->mailer);
        $this->createOAuth();
    }

    /** Even a single e-mail waits for the cron, so it can never run next to the same e-mail in the queue. */
    public function testOneEmailAlsoWaitsInQueue(): void
    {
        $this->createGroup();
        $paymentId = $this->createPayment();

        $dispatch = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), [$paymentId]);

        self::assertSame(1, $dispatch->queued);
        self::assertSame([], $this->mailer->messages);

        $this->users->setUser(null);
        $this->processQueue();

        self::assertCount(1, $this->mailer->messages);
        $this->tester->seeInDatabase('pa_payment_sent_emails', [
            'payment_id' => $paymentId,
            'type' => EmailType::PAYMENT_INFO,
            'sender_name' => self::USER_NAME,
        ]);
    }

    public function testMoreEmailsWaitInQueueAndKeepTheirSender(): void
    {
        $this->createGroup();
        $paymentIds = [$this->createPayment(), $this->createPayment(), $this->createPayment()];

        $dispatch = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), $paymentIds);

        self::assertSame(3, $dispatch->queued);
        self::assertSame([], $this->mailer->messages);

        // The cron runs without a logged-in user.
        $this->users->setUser(null);
        $this->processQueue();

        self::assertCount(3, $this->mailer->messages);
        self::assertSame(3, (int) $this->tester->grabNumRecords('pa_payment_sent_emails', ['sender_name' => self::USER_NAME]));
    }

    public function testEmailWaitingInQueueIsNotQueuedAgain(): void
    {
        $this->createGroup();
        $paymentIds = [$this->createPayment(), $this->createPayment()];

        $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), $paymentIds);
        $again = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), $paymentIds);
        $single = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), [$paymentIds[0]]);
        $this->processQueue();

        self::assertSame(2, $again->alreadyQueued);
        self::assertSame(0, $single->queued);
        self::assertSame(1, $single->alreadyQueued);
        self::assertCount(2, $this->mailer->messages);
    }

    public function testPaymentsOfOtherGroupsAreLeftOut(): void
    {
        $this->createGroup();
        $foreignPaymentId = $this->createPayment();
        $this->createGroup();

        $dispatch = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), [$foreignPaymentId]);

        self::assertSame(0, $dispatch->queued);
        self::assertSame([], $this->mailer->messages);
    }

    public function testReminderOfPaymentThatCannotGetOneIsNotQueued(): void
    {
        $this->createGroup();
        $overdueId = $this->createPayment();
        $notDueYetId = $this->createPayment(ChronosDate::today()->addDays(10));

        $dispatch = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_REMINDER), [$overdueId, $notDueYetId]);

        self::assertSame(1, $dispatch->queued);
        self::assertSame([PaymentEmailDispatch::REMINDER_NOT_ALLOWED => 1], $dispatch->leftOut);
        $this->tester->dontSeeInDatabase('task_queue_item', ['label' => 'Platba '.$notDueYetId]);
    }

    /** A payment without an e-mail has nothing to do in the queue. */
    public function testPaymentWithoutEmailIsNotQueued(): void
    {
        $this->createGroup();
        $withEmailId = $this->createPayment();
        $withoutEmailId = $this->createPayment(withEmail: false);

        $dispatch = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), [$withEmailId, $withoutEmailId]);

        self::assertSame(1, $dispatch->queued);
        self::assertSame([PaymentEmailDispatch::NO_RECIPIENT => 1], $dispatch->leftOut);
        $this->tester->dontSeeInDatabase('task_queue_item', ['label' => 'Platba '.$withoutEmailId]);
    }

    public function testGroupWithoutSenderQueuesNothing(): void
    {
        $this->createGroup(withSender: false);
        $paymentId = $this->createPayment();

        $dispatch = $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_INFO), [$paymentId]);

        self::assertSame(0, $dispatch->queued);
        self::assertNotNull($dispatch->blocked);
        $this->tester->dontSeeInDatabase('task_queue_item', []);
    }

    /** A reminder sent by hand and an automatic one are the same e-mail: only one of them may wait or run. */
    public function testReminderByHandAndAutomaticReminderNeverWaitTogether(): void
    {
        $this->createGroup(reminderDaysAfterDue: 3);
        $paymentId = $this->createPayment(ChronosDate::today()->subDays(5));

        $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_REMINDER), [$paymentId]);
        $this->queueAutomaticReminders();

        self::assertSame(1, (int) $this->tester->grabNumRecords('task_queue_item', ['label' => 'Platba '.$paymentId]));

        $this->processQueue();
        $this->queueAutomaticReminders();
        $this->processQueue();

        self::assertCount(1, $this->mailer->messages);
        $this->tester->seeInDatabase('pa_payment_sent_emails', ['payment_id' => $paymentId, 'sender_name' => self::USER_NAME]);
    }

    public function testReminderOfPaymentPaidMeanwhileIsLeftOut(): void
    {
        $this->createGroup();
        $paidId = $this->createPayment();
        $unpaidId = $this->createPayment();

        $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_REMINDER), [$paidId, $unpaidId]);
        $this->paymentService->completePayment($paidId);
        $this->processQueue();

        self::assertCount(1, $this->mailer->messages);
        $this->tester->seeInDatabase('task_queue_item', ['label' => 'Platba '.$paidId, 'state' => TaskItemState::SKIPPED->value]);
        $this->tester->seeInDatabase('pa_payment_sent_emails', ['payment_id' => $unpaidId, 'type' => EmailType::PAYMENT_REMINDER]);
    }

    public function testAutomaticReminderGoesOutOnceAfterChosenDays(): void
    {
        $this->createGroup(reminderDaysAfterDue: 3);
        $dueLongAgo = $this->createPayment(ChronosDate::today()->subDays(5));
        $dueRecently = $this->createPayment(ChronosDate::today()->subDays(1));
        $remindedByHand = $this->createPayment(ChronosDate::today()->subDays(10));
        $this->emailQueue->send($this->groupId, EmailType::get(EmailType::PAYMENT_REMINDER), [$remindedByHand]);
        $this->processQueue();
        $this->mailer->messages = [];

        $this->queueAutomaticReminders();
        $this->processQueue();
        $this->queueAutomaticReminders();
        $this->processQueue();

        self::assertCount(1, $this->mailer->messages);
        $this->tester->seeInDatabase('pa_payment_sent_emails', [
            'payment_id' => $dueLongAgo,
            'type' => EmailType::PAYMENT_REMINDER,
            'sender_name' => 'AUTOMAT',
        ]);
        $this->tester->dontSeeInDatabase('pa_payment_sent_emails', ['payment_id' => $dueRecently]);
    }

    public function testAutomaticReminderThatCouldNotGoOutIsQueuedAgain(): void
    {
        $this->createGroup(reminderDaysAfterDue: 3);
        $paymentId = $this->createPayment(ChronosDate::today()->subDays(5));
        $this->tester->grabService(MailerFactoryStub::class)->failWith(new InvalidOAuth('invalid_grant => Token has been expired or revoked.'));

        $this->queueAutomaticReminders();
        $this->processQueue();

        $this->tester->seeInDatabase('task_queue_item', ['state' => TaskItemState::FAILED->value]);
        $this->tester->seeInDatabase('pa_payment', ['id' => $paymentId, 'automatic_reminder_at' => null]);

        $this->tester->grabService(MailerFactoryStub::class)->failWith(null);
        $this->queueAutomaticReminders();
        $this->processQueue();

        self::assertCount(1, $this->mailer->messages);
    }

    public function testGroupWithoutChosenDaysSendsNoAutomaticReminder(): void
    {
        $this->createGroup(reminderDaysAfterDue: null);
        $this->createPayment(ChronosDate::today()->subDays(30));

        $this->queueAutomaticReminders();

        $this->tester->dontSeeInDatabase('task_queue_item', []);
    }

    public function testAdministrationCountsGroupsSendingAutomaticReminders(): void
    {
        $this->createGroup(reminderDaysAfterDue: 3);
        $this->createGroup(reminderDaysAfterDue: 7);
        $this->paymentService->closeGroup($this->groupId, 'Uzavřeno v testu');
        $this->createGroup(reminderDaysAfterDue: null);

        self::assertSame(1, $this->tester->grabService(QueryBus::class)->handle(new CountAutomaticReminderGroupsQuery()));
    }

    private function createGroup(?int $reminderDaysAfterDue = null, bool $withSender = true): void
    {
        $this->groupId = $this->paymentService->createGroup(
            11,
            null,
            'Test',
            Helpers::createEmptyPaymentDefaults(),
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('Platba %name%', 'Zaplaťte prosím %amount%.'),
                EmailType::PAYMENT_REMINDER => new EmailTemplate('Upomínka %name%', 'Připomínáme platbu.'),
            ],
            $withSender ? OAuthId::fromString(self::OAUTH_ID) : null,
            null,
            remindersEnabled: true,
            reminderDaysAfterDue: $reminderDaysAfterDue,
        );
    }

    private function createPayment(?ChronosDate $dueDate = null, bool $withEmail = true): int
    {
        $this->tester->grabService(CommandBus::class)->handle(new CreatePayment(
            $this->groupId,
            'Platba',
            $withEmail ? [new EmailAddress(self::EMAIL)] : [],
            MoneyFactory::fromDecimal('100.00'),
            $dueDate ?? Helpers::getValidDueDate(),
            null,
            null,
            null,
            '',
        ));

        $paymentId = (int) $this->entityManager->getConnection()->fetchOne('SELECT MAX(id) FROM pa_payment');
        $this->entityManager->getConnection()->executeStatement('UPDATE pa_payment SET name = ? WHERE id = ?', ['Platba '.$paymentId, $paymentId]);
        $this->entityManager->clear();

        return $paymentId;
    }

    private function queueAutomaticReminders(): void
    {
        $this->entityManager->clear();
        $this->tester->grabService(AutomaticPaymentReminders::class)->queueDue();
    }

    private function processQueue(): void
    {
        $this->tester->grabService(TaskQueueProcessor::class)->processDue(microtime(true) + 60);
    }

    private function createOAuth(): void
    {
        $this->tester->haveInDatabase('google_oauth', [
            'id' => self::OAUTH_ID,
            'unit_id' => 27266,
            'email' => self::EMAIL,
            'token' => 'token',
            'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
