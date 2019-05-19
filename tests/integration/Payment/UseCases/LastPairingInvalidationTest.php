<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use eGen\MessageBus\Bus\CommandBus;
use Helpers;
use IntegrationTest;
use Model\BankService;
use Model\Payment\Commands\BankAccount\CreateBankAccount;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\Commands\Payment\UpdatePayment;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Services\BankAccountAccessChecker;

final class LastPairingInvalidationTest extends IntegrationTest
{
    private const UNIT_ID                  = 101;
    private const ORIGINAL_VARIABLE_SYMBOL = '1001';
    private const ORIGINAL_AMOUNT          = 200;

    /** @var CommandBus */
    private $commandBus;

    /** @var IGroupRepository */
    private $groupRepository;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            BankAccount::class,
            Group::class,
            Group\Unit::class,
            Group\Email::class,
            Payment::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles([__DIR__ . '/LastPairingInvalidationTest.neon']);

        parent::_before();
        $this->tester->grabService(UnitResolverStub::class)
            ->setOfficialUnits([self::UNIT_ID => self::UNIT_ID]);

        $this->commandBus      = $this->tester->grabService(CommandBus::class);
        $this->groupRepository = $this->tester->grabService(IGroupRepository::class);

        $this->createBankAccount();
        $this->createGroupWithInitialPayment();
        $this->pairPayments();
    }

    public function testLastPairingIsInvalidatedWhenNewPaymentIsCreated() : void
    {
        $this->commandBus->handle(
            new CreatePayment(1, 'a', null, 2, Helpers::getValidDueDate(), null, new VariableSymbol('1'), null, '')
        );

        $this->assertGroupHasEmptyLastPairing();
    }

    public function testLastPairingIsInvalidatedWhenPaymentAmountIsChanged() : void
    {
        $this->commandBus->handle(
            new UpdatePayment(
                1,
                'a',
                null,
                self::ORIGINAL_AMOUNT + 1,
                Helpers::getValidDueDate(),
                new VariableSymbol(self::ORIGINAL_VARIABLE_SYMBOL),
                null,
                ''
            )
        );

        $this->assertGroupHasEmptyLastPairing();
    }

    public function testLastPairingIsInvalidatedWhenPaymentVariableSymbolIsChanged() : void
    {
        $this->commandBus->handle(
            new UpdatePayment(
                1,
                'a',
                null,
                self::ORIGINAL_AMOUNT,
                Helpers::getValidDueDate(),
                (new VariableSymbol(self::ORIGINAL_VARIABLE_SYMBOL))->increment(),
                null,
                ''
            )
        );

        $this->assertGroupHasEmptyLastPairing();
    }

    public function testLastPairingIsNotInvalidatedWhenPaymentVariableSymbolIsRemoved() : void
    {
        $this->commandBus->handle(
            new UpdatePayment(
                1,
                'a',
                null,
                self::ORIGINAL_AMOUNT,
                Helpers::getValidDueDate(),
                null,
                null,
                ''
            )
        );

        $this->assertGroupHasLastPairing();
    }

    private function pairPayments() : void
    {
        $this->tester->grabService(BankService::class)->pairAllGroups([1]);
    }

    private function createBankAccount() : void
    {
        $this->commandBus->handle(
            new CreateBankAccount(
                self::UNIT_ID,
                'foo',
                Helpers::createAccountNumber(),
                '1234'
            )
        );
    }

    private function createGroupWithInitialPayment() : void
    {
        $this->groupRepository->save(
            new Group(
                [self::UNIT_ID],
                null,
                'x',
                Helpers::createEmptyPaymentDefaults(),
                new DateTimeImmutable(),
                [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
                null,
                $this->entityManager->find(BankAccount::class, 1),
                $this->tester->grabService(BankAccountAccessChecker::class)
            )
        );

        // we need at least one payment for initial pairing
        $this->commandBus->handle(
            new CreatePayment(
                1,
                'x',
                null,
                self::ORIGINAL_AMOUNT,
                Helpers::getValidDueDate(),
                null,
                new VariableSymbol('123'),
                null,
                ''
            )
        );
    }

    private function assertGroupHasEmptyLastPairing() : void
    {
        $this->assertNull($this->groupRepository->find(1)->getLastPairing());
    }

    private function assertGroupHasLastPairing() : void
    {
        $this->assertNotNull($this->groupRepository->find(1)->getLastPairing());
    }
}
