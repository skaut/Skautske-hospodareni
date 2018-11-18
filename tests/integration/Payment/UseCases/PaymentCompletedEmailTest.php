<?php

declare(strict_types=1);

namespace Model\Payment\IntegrationTests;

use Model\Common\User;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentials\MailProtocol;
use Model\Payment\Payment;
use Model\Payment\UserRepositoryStub;
use Model\PaymentService;

class PaymentCompletedEmailTest extends \IntegrationTest
{
    private const UNIT_ID = 10;
    private const EMAIL   = 'test@hospodareni.loc';

    /** @var PaymentService */
    private $paymentService;

    /** @var UserRepositoryStub */
    private $users;

    /**
     * @return string[]
     */
    protected function getTestedEntites() : array
    {
        return [
            Group::class,
            Group\Email::class,
            Payment::class,
            MailCredentials::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['Payment/UseCases/PaymentCompletedEmailTest.neon']);
        parent::_before();
        $this->paymentService = $this->tester->grabService(PaymentService::class);
        $this->users          = $this->tester->grabService(UserRepositoryStub::class);
    }

    public function testWhenEmailIsNotSetNothingHappens() : void
    {
        $this->createMailCredentials();
        $this->initEntities();

        $this->paymentService->completePayment(1);

        $this->tester->seeEmailCount(0);
    }

    /**
     * @see bug https://github.com/skaut/Skautske-hospodareni/pull/511
     */
    public function testWhenPaymentHasNoEmailNothingHappens() : void
    {
        $this->createMailCredentials();
        $this->initEntities([
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
            EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
        ], null);

        $this->paymentService->completePayment(1);

        $this->tester->seeEmailCount(0);
    }

    /**
     * @see bug https://github.com/skaut/Skautske-hospodareni/pull/511
     */
    public function testWhenGroupHasNoMailCredentialsSetNothingHappens() : void
    {
        $this->initEntities([
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
            EmailType::PAYMENT_COMPLETED => new EmailTemplate('subject', 'body'),
        ], self::EMAIL, null);

        $this->paymentService->completePayment(1);

        $this->tester->seeEmailCount(0);
    }

    public function testEmailIsSentWhenPaymentIsCompleted() : void
    {
        $email = new EmailTemplate('subject', 'body');
        $this->createMailCredentials();
        $this->initEntities([
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
            EmailType::PAYMENT_COMPLETED => $email,
        ]);

        $this->users->setUser(new User(1, 'František Maša', 'frantisekmasa1@gmail.com'));

        $this->paymentService->completePayment(1);

        $this->tester->seeEmailCount(1);
        $this->tester->seeInLastEmailSubjectTo(self::EMAIL, $email->getSubject());
        $this->tester->seeInLastEmailTo(self::EMAIL, $email->getBody());
    }

    /**
     * @param EmailTemplate[]|null $emails
     */
    private function initEntities(?array $emails = null, ?string $paymentEmail = self::EMAIL, ?int $credentialsId = 1) : void
    {
        $this->tester->resetEmails();

        $paymentDefaults = \Helpers::createEmptyPaymentDefaults();
        $emails          = $emails ?? [
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
        ];

        $this->paymentService->createGroup(11, null, 'Test', $paymentDefaults, $emails, $credentialsId, null);
        $this->paymentService->createPayment(
            1,
            'Platba',
            $paymentEmail,
            100,
            \Helpers::getValidDueDate(),
            null,
            null,
            null,
            ''
        );
    }

    private function createMailCredentials() : void
    {
        $this->tester->haveInDatabase('pa_smtp', [
            'unitId' => self::UNIT_ID,
            'host' => 'smtp-hospodareni.loc',
            'secure' => MailProtocol::PLAIN,
            'username' => 'test@hospodareni.loc',
            'password' => '',
            'sender' => 'test@hospodareni.loc',
            'created' => '2017-10-01 00:00:00',
        ]);
    }
}
