<?php

namespace Tests\Integration\Pairing;

use Model\Bank\Fio\Transaction;
use Model\BankService;
use Model\Payment\FioClientStub;
use Model\Payment\Group;
use Nette\Utils\Random;

class BankServiceTest extends \IntegrationTest
{

    /** @var \IntegrationTester */
    protected $tester;

    /** @var BankService */
    private $bankService;


    protected function _before()
    {
        $this->tester->useConfigFiles(['Payment/bank.service.neon']);
        parent::_before();
        $this->bankService = $this->tester->grabService(BankService::class);
    }


    public function testPairGroups()
    {
        $I = $this->tester;

        $I->haveInDatabase('pa_bank_account', [
            'unit_id' => 1,
            'name' => 'Hlavní',
            'token' => 'test-token',
            'number_number' => '2000942144',
            'number_bank_code' => '2010',
            'allowed_for_subunits' => 1,
            'created_at' => '2017-06-06'
        ]);

        $this->addGroup(1); // ID: 1
        $this->addGroup(1); // ID: 2

        $this->addPayment(1, 200, '123');
        $this->addPayment(1, 400, '345');
        $this->addPayment(2, 400, '345');


        $I->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createTransaction(200, '123'),
                $this->createTransaction(400, '345'),
                $this->createTransaction(500, '')
            ]);

        $this->assertSame(
            2,
            $this->bankService->pairAllGroups([1])
        );
    }


    private function addPayment(int $groupId, float $amount, ?string $variableSymbol)
    {
        $this->tester->haveInDatabase('pa_payment', [
            'name' => Random::generate(),
            'groupId' => $groupId,
            'amount' => $amount,
            'vs' => $variableSymbol,
            'maturity' => '2017-06-10',
        ]);
    }


    private function addGroup(?int $bankAccountId)
    {
        $this->tester->haveInDatabase('pa_group', [
            'unitId' => 1,
            'label' => 'Testovaci skupina',
            'email_template_subject' => '',
            'email_template_body' => '',
            'state' => Group::STATE_OPEN,
            'created_at' => '2017-06-07',
            'bank_account_id' => $bankAccountId,
        ]);
    }


    private function createTransaction(float $amount, ?string $variableSymbol): Transaction
    {
        return new Transaction(
            mt_rand(1, 1000),
            new \DateTimeImmutable(),
            $amount,
            '',
            '',
            (int)$variableSymbol,
            0,
            ''
        );
    }

}
