<?php

declare(strict_types=1);

namespace integration\Payment\Repositories;

use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\GroupNotFoundException;
use Model\Payment\Repositories\GroupRepository;
use Model\Payment\VariableSymbol;

class GroupRepositoryTest extends \IntegrationTest
{

    /** @var GroupRepository */
    private $repository;

    public function getTestedEntites(): array
    {
        return [
            Group::class,
            Group\Email::class,
        ];
    }

    protected function _before()
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new GroupRepository($this->entityManager);
    }

    public function testFindNotSavedGroupThrowsException(): void
    {
        $this->expectException(GroupNotFoundException::class);

        $this->repository->find(10);
    }

    public function testFind(): void
    {
        $createdAt = new \DateTimeImmutable('2018-01-01 00:00:00');
        $lastPairing = new \DateTimeImmutable('2018-01-19 18:34:00');
        $paymentDefaults = new Group\PaymentDefaults(
            100,
            new \DateTimeImmutable('2018-01-29 00:00:00'),
            123,
            new VariableSymbol('140')
        );

        $row = [
            'label' => 'Test',
            'state' => Group::STATE_OPEN,
            'state_info' => 'Test note',
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'unitId' => 12,
            'last_pairing' => $lastPairing->format('Y-m-d H:i:s'),
            'smtp_id' => 10,
            'bank_account_id' => 100,
            'amount' => $paymentDefaults->getAmount(),
            'nextVs' => $paymentDefaults->getNextVariableSymbol()->toInt(),
            'maturity' => $paymentDefaults->getDueDate()->format('Y-m-d H:i:s'),
            'ks' => $paymentDefaults->getConstantSymbol(),
        ];

        $infoEmail = new EmailTemplate('subject', 'body');

        $this->tester->haveInDatabase('pa_group', $row);
        $this->tester->haveInDatabase('pa_group_email', [
            'group_id' => 1,
            'template_subject' => $infoEmail->getSubject(),
            'template_body' => $infoEmail->getBody(),
            'type' => EmailType::PAYMENT_INFO,
        ]);

        $group = $this->repository->find(1);

        $this->assertSame($row['state'], $group->getState());
        $this->assertSame($row['state_info'], $group->getNote());
        $this->assertEquals($createdAt, $group->getCreatedAt());
        $this->assertSame($row['unitId'], $group->getUnitId());
        $this->assertEquals($lastPairing, $group->getLastPairing());
        $this->assertSame($row['smtp_id'], $group->getSmtpId());
        $this->assertSame($row['bank_account_id'], $group->getBankAccountId());
        $this->assertSame($paymentDefaults->getAmount(), $group->getPaymentDefaults()->getAmount());
        $this->assertEquals($paymentDefaults->getDueDate(), $group->getPaymentDefaults()->getDueDate());
        $this->assertSame($paymentDefaults->getConstantSymbol(), $group->getPaymentDefaults()->getConstantSymbol());
        $this->assertEquals($paymentDefaults->getNextVariableSymbol(), $group->getPaymentDefaults()->getNextVariableSymbol());
        $this->assertTrue($infoEmail->equals($group->getEmailTemplates()[EmailType::PAYMENT_INFO]));
    }

}
