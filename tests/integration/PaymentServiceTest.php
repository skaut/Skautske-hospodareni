<?php

declare(strict_types=1);

namespace Model;

use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\VariableSymbol;

class PaymentServiceTest extends \IntegrationTest
{
    /** @var PaymentService */
    private $service;

    /** @var IPaymentRepository */
    private $paymentRepository;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            Group::class,
            Group\Email::class,
            Payment::class,
        ];
    }

    public function _before() : void
    {
        $this->tester->useConfigFiles(['PaymentServiceTest.neon']);
        parent::_before();
        $this->service           = $this->tester->grabService(PaymentService::class);
        $this->paymentRepository = $this->tester->grabService(IPaymentRepository::class);
    }

    /**
     * @see https://github.com/skaut/Skautske-hospodareni/issues/387
     */
    public function testGenerateVSForMultiplePayments() : void
    {
        $paymentDefaults = new Group\PaymentDefaults(null, null, null, new VariableSymbol('1'));
        $emails          = \Helpers::createEmails();

        $this->service->createGroup(10, null, 'test group', $paymentDefaults, $emails, null, null);

        for ($i = 0; $i < 5; $i++) {
            $this->createPaymentWithoutVariableSymbol(1);
        }

        $this->service->generateVs(1);

        $payments                = $this->paymentRepository->findByGroup(1);
        $expectedVariableSymbols = [1, 2, 3, 4, 5];

        foreach ($expectedVariableSymbols as $index => $variableSymbol) {
            $this->assertSame($variableSymbol, $payments[$index]->getVariableSymbol()->toInt());
        }
    }

    private function createPaymentWithoutVariableSymbol(int $groupId) : void
    {
        $this->service->createPayment(
            $groupId,
            'test',
            null,
            100,
            new \DateTimeImmutable(),
            null,
            null,
            null,
            ''
        );
    }
}
