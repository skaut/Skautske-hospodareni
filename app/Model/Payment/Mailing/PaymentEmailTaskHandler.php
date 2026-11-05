<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Mail\IMailerFactory;
use App\Model\Payment\BankAccountNotFound;
use App\Model\Payment\EmailTemplateNotSet;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\MailingService;
use App\Model\Payment\Payment;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentHasNoEmails;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\TaskQueue\Exception\TaskFailed;
use App\Model\TaskQueue\Exception\TaskRetryLater;
use App\Model\TaskQueue\Exception\TaskSkipped;
use App\Model\TaskQueue\TaskContext;
use App\Model\TaskQueue\TaskHandler;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\Exception as GoogleServiceException;
use Throwable;

use function in_array;
use function is_array;
use function sprintf;

/**
 * Sends one e-mail of a payment from the task queue. The state of the payment is checked again right
 * before sending, so e.g. a reminder of a payment paid meanwhile is left out.
 */
final class PaymentEmailTaskHandler implements TaskHandler
{
    public const TYPE = 'payment_email';

    /** Sender name of e-mails nobody started by hand, as the cron always used. */
    public const AUTOMATIC_SENDER = 'AUTOMAT';

    private const GMAIL_LIMIT_REASONS = ['rateLimitExceeded', 'userRateLimitExceeded', 'dailyLimitExceeded'];
    private const GMAIL_LIMIT_RETRY_SECONDS = 3600;

    public function __construct(
        private IPaymentRepository $payments,
        private IGroupRepository $groups,
        private MailingService $mailingService,
        private IMailerFactory $mailerFactory,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'E-maily plateb';
    }

    public function handle(TaskContext $context): void
    {
        $task = PaymentEmailTask::fromPayload($context->getPayload());
        $payment = $this->findPayment($task->paymentId);
        $group = $this->findGroup($payment->getGroupId());
        $senderName = $context->getCreatedByName() ?? self::AUTOMATIC_SENDER;

        $this->assertStillNeeded($task, $payment);

        if ($group->getOauthId() === null) {
            throw new TaskFailed('Skupina nemá nastavený e-mail odesílatele.', abortJob: true);
        }

        $email = $this->compose($payment, $group, $task->emailType, $senderName);

        try {
            $mailer = $this->mailerFactory->create($email->sender);
        } catch (InvalidOAuth $exception) {
            throw new TaskFailed($exception->getExplainedMessage(), abortJob: true, previous: $exception);
        }

        $context->beginIrreversible();

        try {
            $mailer->send($email->message);
        } catch (Throwable $exception) {
            // The Mailer interface declares only its own exception, OAuthMailer passes on the ones of Gmail.
            if ($exception instanceof GoogleServiceException) {
                $this->handleRefusedEmail($exception);
            }

            throw $exception;
        }

        $context->complete(function () use ($payment, $task, $senderName): void {
            $payment->recordSentEmail($task->emailType, new DateTimeImmutable(), $senderName);
            $this->payments->save($payment);
        });
    }

    /** An automatic reminder that did not go out may go out on a later day. */
    public function release(array $payload): void
    {
        $task = PaymentEmailTask::fromPayload($payload);

        if (! $task->automatic) {
            return;
        }

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE pa_payment SET automatic_reminder_at = NULL WHERE id = ?',
            [$task->paymentId],
        );
    }

    private function findPayment(int $paymentId): Payment
    {
        try {
            return $this->payments->find($paymentId);
        } catch (PaymentNotFound) {
            throw new TaskSkipped('Platba už neexistuje.');
        }
    }

    private function findGroup(int $groupId): Group
    {
        try {
            return $this->groups->find($groupId);
        } catch (GroupNotFound) {
            throw new TaskSkipped('Platební skupina už neexistuje.');
        }
    }

    private function assertStillNeeded(PaymentEmailTask $task, Payment $payment): void
    {
        $type = $task->emailType->getValue();

        if ($type === EmailType::PAYMENT_COMPLETED) {
            if (! $payment->getState()->equalsValue(State::COMPLETED)) {
                throw new TaskSkipped('Platba už není zaplacená.');
            }

            return;
        }

        if ($payment->isClosed()) {
            throw new TaskSkipped('Platba je už uzavřená.');
        }

        if ($type !== EmailType::PAYMENT_REMINDER) {
            return;
        }

        if ($task->automatic && $payment->hasSentReminder()) {
            throw new TaskSkipped('Upomínka už byla odeslána.');
        }

        if ($payment->hasSentReminderToday()) {
            throw new TaskSkipped('Upomínka už dnes odešla.');
        }

        if (! $payment->canSendReminder()) {
            throw new TaskSkipped('Platba ještě není po splatnosti.');
        }
    }

    private function compose(Payment $payment, Group $group, EmailType $emailType, string $senderName): ComposedEmail
    {
        try {
            return $this->mailingService->composeForPayment($payment, $group, $emailType, $senderName);
        } catch (EmailTemplateNotSet) {
            throw new TaskFailed('Skupina nemá zapnutou šablonu tohoto e-mailu.', abortJob: true);
        } catch (OAuthNotSet) {
            throw new TaskFailed('Skupina nemá nastavený e-mail odesílatele.', abortJob: true);
        } catch (OAuthNotFound) {
            throw new TaskFailed('E-mail odesílatele skupiny už není propojený.', abortJob: true);
        } catch (PaymentHasNoEmails) {
            throw new TaskFailed('Platba nemá vyplněný e-mail.');
        } catch (InvalidBankAccount|BankAccountNotFound) {
            throw new TaskFailed('Šablona e-mailu potřebuje bankovní účet, který skupina nemá.', abortJob: true);
        }
    }

    /**
     * Gmail answered, so it is known whether it took the e-mail. A limit or a refused e-mail did not go out;
     * anything else (e.g. a server error or a lost connection) leaves the e-mail uncertain.
     */
    private function handleRefusedEmail(GoogleServiceException $exception): never
    {
        $code = $exception->getCode();

        if ($code === 429 || ($code === 403 && $this->hasLimitReason($exception))) {
            throw new TaskRetryLater('Gmail dočasně neodesílá další e-maily kvůli limitu odesílání.', self::GMAIL_LIMIT_RETRY_SECONDS, $exception);
        }

        if ($code >= 400 && $code < 500) {
            throw new TaskFailed(sprintf('Gmail e-mail odmítl: %s', $exception->getMessage()), previous: $exception);
        }

        throw $exception;
    }

    private function hasLimitReason(GoogleServiceException $exception): bool
    {
        foreach ($exception->getErrors() ?? [] as $error) {
            if (is_array($error) && in_array($error['reason'] ?? null, self::GMAIL_LIMIT_REASONS, true)) {
                return true;
            }
        }

        return false;
    }
}
