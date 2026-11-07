<?php

declare(strict_types=1);

namespace App\Model\Payment\IntegrationTests;

use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\User;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\InvalidOAuth;
use App\Model\Google\OAuthId;
use App\Model\Payment\Commands\Payment\CreatePayment;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\MailerFactoryStub;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentService;
use App\Model\Payment\RecordingMailer;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\UserRepositoryStub;
use App\Model\TaskQueue\Entity\TaskItem;
use App\Model\TaskQueue\Entity\TaskQueueRun;
use App\Model\TaskQueue\TaskItemState;
use App\Model\TaskQueue\TaskQueueProcessor;
use App\Model\Utils\MoneyFactory;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use RuntimeException;
use Skautis\Wsdl\WsdlException;

use function microtime;

class PaymentCompletedEmailTest extends IntegrationTest
{
    private const EMAIL = 'test@hospodareni.loc';

    private PaymentService $paymentService;

    private UserRepositoryStub $users;

    private CommandBus $commandBus;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Group::class,
            Payment::class,
            GoogleOAuth::class,
            TaskItem::class,
            TaskQueueRun::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Payment/UseCases/PaymentCompletedEmailTest.neon']);

        parent::_before();

        $this->paymentService = $this->tester->grabService(PaymentService::class);
        $this->users = $this->tester->grabService(UserRepositoryStub::class);
        $this->commandBus = $this->tester->grabService(CommandBus::class);
    }

    /** @see bug https://github.com/skaut/Skautske-hospodareni/pull/511 */
    public function testWhenPaymentHasNoEmailNothingHappens(): void
    {
        $this->users->setUser(new User(10, 'František Maša', self::EMAIL));
        $oauthId = $this->createOAuth();
        $this->initEntities(
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
                EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
            ],
            null,
            $oauthId,
        );

        $this->paymentService->completePayment(1);

        $this->assertPaymentWasCompleted();
        $this->tester->dontSeeInDatabase('task_queue_item', []);
    }

    /** @see bug https://github.com/skaut/Skautske-hospodareni/pull/511 */
    public function testWhenGroupHasNoOAuthSetNothingHappens(): void
    {
        $this->users->setUser(new User(10, 'František Maša', self::EMAIL));
        $this->initEntities([
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
            EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
        ], self::EMAIL, null);

        $this->paymentService->completePayment(1);

        $this->assertPaymentWasCompleted();
    }

    public function testEmailIsSentWhenPaymentIsCompleted(): void
    {
        $email = new EmailTemplate('subject', 'body');
        $oAuthId = $this->createOAuth();
        $this->initEntities(
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
                EmailType::PAYMENT_COMPLETED => $email,
            ],
            self::EMAIL,
            $oAuthId,
        );

        $this->users->setUser(new User(1, 'František Maša', self::EMAIL));
        $mailer = $this->useRecordingMailer();

        $this->paymentService->completePayment(1);

        // The e-mail waits in the queue and keeps the name of who completed the payment.
        self::assertSame([], $mailer->messages);
        $this->users->setUser(null);
        $this->processQueue();

        self::assertCount(1, $mailer->messages);
        $this->tester->seeInDatabase('pa_payment_sent_emails', [
            'payment_id' => 1,
            'type' => EmailType::PAYMENT_COMPLETED,
            'sender_name' => 'František Maša',
        ]);
    }

    public function testNothingIsQueuedWhenCompletionIsRolledBack(): void
    {
        $this->users->setUser(new User(1, 'František Maša', self::EMAIL));
        $this->initEntities(
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
                EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
            ],
            self::EMAIL,
            $this->createOAuth(),
        );

        try {
            $this->entityManager->wrapInTransaction(function (): void {
                $this->paymentService->completePayment(1);

                throw new RuntimeException('Saving failed.');
            });
        } catch (RuntimeException) {
        }

        $this->tester->dontSeeInDatabase('task_queue_item', []);
    }

    public function testEmailIsSentAsAutomatWhenNobodyIsLoggedIn(): void
    {
        $this->users->setUser(new User(10, 'František Maša', self::EMAIL));
        $this->initEntities(
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
                EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
            ],
            self::EMAIL,
            $this->createOAuth(),
        );
        $this->users->setUser(null);
        $payments = $this->tester->grabService(IPaymentRepository::class);
        $payment = $payments->find(1);

        $payment->pairWithTransaction(new DateTimeImmutable(), new Transaction('21924318042', '123456789/0800', 'Joe Doe', null, null));
        $payments->save($payment);
        $this->processQueue();

        $this->assertPaymentWasCompleted();
        $this->tester->seeInDatabase('pa_payment_sent_emails', [
            'payment_id' => 1,
            'type' => EmailType::PAYMENT_COMPLETED,
            'sender_name' => 'AUTOMAT',
        ]);
    }

    /** Who completed the payment is only for the record; skautIS failing to tell must not roll back the payment. */
    public function testPaymentIsCompletedWhenSkautisCannotTellWhoCompletedIt(): void
    {
        $this->users->setUser(new User(10, 'František Maša', self::EMAIL));
        $this->initEntities(
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
                EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
            ],
            self::EMAIL,
            $this->createOAuth(),
        );
        $this->users->failWith(new WsdlException('Service Unavailable'));
        $mailer = $this->useRecordingMailer();

        $this->paymentService->completePayment(1);

        $this->users->failWith(null);
        $this->users->setUser(null);
        $this->processQueue();

        $this->assertPaymentWasCompleted();
        self::assertCount(1, $mailer->messages);
        $this->tester->seeInDatabase('pa_payment_sent_emails', [
            'payment_id' => 1,
            'type' => EmailType::PAYMENT_COMPLETED,
            'sender_name' => 'AUTOMAT',
        ]);
    }

    public function testPaymentIsCompletedWhenGroupOAuthIsInvalid(): void
    {
        $this->users->setUser(new User(10, 'František Maša', self::EMAIL));
        $this->initEntities(
            [
                EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
                EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
            ],
            self::EMAIL,
            $this->createOAuth(),
        );
        $this->tester->grabService(MailerFactoryStub::class)->failWith(new InvalidOAuth('invalid_grant => Token has been expired or revoked.'));

        $this->paymentService->completePayment(1);
        $this->processQueue();

        $this->assertPaymentWasCompleted();
        $this->tester->seeInDatabase('task_queue_item', ['state' => TaskItemState::FAILED->value]);
        $this->tester->dontSeeInDatabase('pa_payment_sent_emails', ['payment_id' => 1]);
    }

    /** @param EmailTemplate[]|null $emails */
    private function initEntities(?array $emails = null, ?string $paymentEmail = self::EMAIL, ?OAuthId $oAuthId = null): void
    {
        if ($oAuthId === null) {
            $oAuthId = OAuthId::generate();
        }

        $paymentDefaults = Helpers::createEmptyPaymentDefaults();
        $emails ??= [
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
        ];

        $this->paymentService->createGroup(11, null, 'Test', $paymentDefaults, $emails, $oAuthId, null);
        $this->commandBus->handle(
            new CreatePayment(1, 'Platba', $paymentEmail !== null ? [new EmailAddress($paymentEmail)] : [], MoneyFactory::fromDecimal('100.00'), Helpers::getValidDueDate(), null, null, null, ''),
        );
    }

    private function createOAuth(string $password = ''): OAuthId
    {
        $id = '42288e92-27fb-453c-9904-36a7ebd14fe2';
        $this->tester->haveInDatabase('google_oauth', [
            'id' => $id,
            'unit_id' => 27266,
            'email' => self::EMAIL,
            'token' => '1//02yV7BM31saaQCgYIAPOOREPSNwF-L9Irbcw-iJEHRUnfxt2KULTjXQkPI-jl8LEN-SwVp6OybduZT21RiDf7RZBA4ZoZu86UXC8',
            'updated_at' => '2017-06-15 00:00:00',
        ]);

        return OAuthId::fromString('42288e92-27fb-453c-9904-36a7ebd14fe2');
    }

    private function useRecordingMailer(): RecordingMailer
    {
        $mailer = new RecordingMailer();
        $this->tester->grabService(MailerFactoryStub::class)->setMailer($mailer);

        return $mailer;
    }

    private function processQueue(): void
    {
        $this->tester->grabService(TaskQueueProcessor::class)->processDue(microtime(true) + 60);
    }

    private function assertPaymentWasCompleted(): void
    {
        $this->tester->seeInDatabase('pa_payment', [
            'id' => 1,
            'state' => Payment\State::COMPLETED,
        ]);
    }
}
