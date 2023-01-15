<?php

declare(strict_types=1);

namespace Model\Payment\IntegrationTests;

use Helpers;
use IntegrationTest;
use Model\Common\EmailAddress;
use Model\Common\Services\CommandBus;
use Model\Common\User;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\UserRepositoryStub;
use Model\PaymentService;

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
            OAuth::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Payment/UseCases/PaymentCompletedEmailTest.neon']);
        parent::_before();
        $this->paymentService = $this->tester->grabService(PaymentService::class);
        $this->users          = $this->tester->grabService(UserRepositoryStub::class);
        $this->commandBus     = $this->tester->grabService(CommandBus::class);
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
        $email   = new EmailTemplate('subject', 'body');
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

        $this->paymentService->completePayment(1);
    }

    /** @param EmailTemplate[]|null $emails */
    private function initEntities(array|null $emails = null, string|null $paymentEmail = self::EMAIL, OAuthId|null $oAuthId = null): void
    {
        if ($oAuthId === null) {
            $oAuthId = OAuthId::generate();
        }

        $paymentDefaults = Helpers::createEmptyPaymentDefaults();
        $emails        ??= [
            EmailType::PAYMENT_INFO => new EmailTemplate('', ''),
        ];

        $this->paymentService->createGroup(11, null, 'Test', $paymentDefaults, $emails, $oAuthId, null);
        $this->commandBus->handle(
            new CreatePayment(1, 'Platba', $paymentEmail !== null ? [new EmailAddress($paymentEmail)] : [], 100, Helpers::getValidDueDate(), null, null, null, ''),
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

    private function assertPaymentWasCompleted(): void
    {
        $this->tester->seeInDatabase('pa_payment', [
            'id' => 1,
            'state' => Payment\State::COMPLETED,
        ]);
    }
}
