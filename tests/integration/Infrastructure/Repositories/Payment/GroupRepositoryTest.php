<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\EventBus;
use Mockery as m;
use Model\Payment\DomainEvents\GroupWasRemoved;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\GroupNotFound;
use Model\Payment\VariableSymbol;
use function array_map;
use function sort;

class GroupRepositoryTest extends \IntegrationTest
{
    /** @var GroupRepository */
    private $repository;

    /** @var EventBus */
    private $eventBus;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            Group::class,
            Group\Email::class,
            Group\Unit::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();

        $this->eventBus   = m::mock(EventBus::class);
        $this->repository = new GroupRepository($this->entityManager, $this->eventBus);
    }

    public function testFindNotSavedGroupThrowsException() : void
    {
        $this->expectException(GroupNotFound::class);

        $this->repository->find(10);
    }

    public function testFind() : void
    {
        $createdAt       = new \DateTimeImmutable('2018-01-01 00:00:00');
        $lastPairing     = new \DateTimeImmutable('2018-01-19 18:34:00');
        $paymentDefaults = new Group\PaymentDefaults(
            100.0,
            new Date('2018-01-29'),
            123,
            new VariableSymbol('140')
        );

        $row = [
            'label' => 'Test',
            'state' => Group::STATE_OPEN,
            'state_info' => 'Test note',
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'last_pairing' => $lastPairing->format('Y-m-d H:i:s'),
            'smtp_id' => 10,
            'bank_account_id' => 100,
            'amount' => $paymentDefaults->getAmount(),
            'nextVs' => $paymentDefaults->getNextVariableSymbol()->toInt(),
            'maturity' => $paymentDefaults->getDueDate()->format('Y-m-d'),
            'ks' => $paymentDefaults->getConstantSymbol(),
        ];

        $infoEmail = new EmailTemplate('subject', 'body');

        $this->tester->haveInDatabase('pa_group', $row);
        $this->tester->haveInDatabase('pa_group_email', [
            'group_id' => 1,
            'template_subject' => $infoEmail->getSubject(),
            'template_body' => $infoEmail->getBody(),
            'type' => EmailType::PAYMENT_INFO,
            'enabled' => 1,
        ]);

        // Different order is there to check that units are sorted by index column
        $unitIds = [9, 10];

        foreach ($unitIds as $unitId) {
            $this->tester->haveInDatabase('pa_group_unit', [
                'group_id' => 1,
                'unit_id' => $unitId,
            ]);
        }

        $group = $this->repository->find(1);

        $this->assertSame($row['state'], $group->getState());
        $this->assertSame($row['state_info'], $group->getNote());
        $this->assertEquals($createdAt, $group->getCreatedAt());
        $this->assertSame($unitIds, $group->getUnitIds());
        $this->assertEquals($lastPairing, $group->getLastPairing());
        $this->assertSame($row['smtp_id'], $group->getSmtpId());
        $this->assertSame($row['bank_account_id'], $group->getBankAccountId());
        $this->assertSame($paymentDefaults->getAmount(), $group->getPaymentDefaults()->getAmount());
        $this->assertEquals($paymentDefaults->getDueDate(), $group->getPaymentDefaults()->getDueDate());
        $this->assertSame($paymentDefaults->getConstantSymbol(), $group->getPaymentDefaults()->getConstantSymbol());
        $this->assertEquals($paymentDefaults->getNextVariableSymbol(), $group->getPaymentDefaults()->getNextVariableSymbol());
        $this->assertTrue($infoEmail->equals($group->getEmailTemplate(EmailType::get(EmailType::PAYMENT_INFO))));
        $this->assertTrue($group->isEmailEnabled(EmailType::get(EmailType::PAYMENT_INFO)));
        $this->assertNull($group->getObject());
    }

    public function testFindByUnits() : void
    {
        $row = [
            'label' => 'Test',
            'state' => Group::STATE_OPEN,
            'state_info' => 'Test note',
            'created_at' => '2018-01-01 00:00:00',
            'last_pairing' => '2018-01-01 00:00:00',
            'smtp_id' => 10,
            'bank_account_id' => 100,
            'maturity' => '2018-01-01',
            'ks' => null,
        ];

        foreach ([10, 5, 20] as $index => $unitId) {
            $this->tester->haveInDatabase('pa_group', $row);

            // Unrelated unit
            $this->tester->haveInDatabase('pa_group_unit', [
                'group_id' => $index + 1,
                'unit_id' => 100,
            ]);

            $this->tester->haveInDatabase('pa_group_unit', [
                'group_id' => $index + 1,
                'unit_id' => $unitId,
            ]);
        }

        $groups = $this->repository->findByUnits([10, 20], false);

        $groupIds = array_map(
            function (Group $group) : int {
                return $group->getId();
            },
            $groups
        );

        sort($groupIds);

        $this->assertSame([1, 3], $groupIds);
    }

    public function testRemoveRemovesGroupFromDatabase() : void
    {
        $this->eventBus->shouldReceive('handle')
            ->once()
            ->withArgs(function (GroupWasRemoved $event) : bool {
                return $event->getGroupId() === 1;
            });

        $I = $this->tester;

        $I->haveInDatabase('pa_group', [
            'label' => 'Test',
            'state' => Group::STATE_CLOSED,
            'state_info' => 'Test note',
            'created_at' => '2018-05-14 00:00:00',
            'last_pairing' => '2018-05-14 00:00:00',
            'smtp_id' => 10,
            'bank_account_id' => 100,
            'amount' => 100.0,
            'nextVs' => 123,
            'maturity' => '2018-05-14 00:00:00',
            'ks' => 123,
        ]);

        $I->haveInDatabase('pa_group_email', [
            'group_id' => 1,
            'template_subject' => 'test',
            'template_body' => '',
            'type' => EmailType::PAYMENT_INFO,
            'enabled' => 1,
        ]);

        $I->haveInDatabase('pa_group_unit', [
            'group_id' => 1,
            'unit_id' => 5,
        ]);

        $group = $this->repository->find(1);

        $this->repository->remove($group);

        $I->dontSeeInDatabase('pa_group', ['id' => 1]);
        $I->dontSeeInDatabase('pa_group_email', ['group_id' => 1]);
        $I->dontSeeInDatabase('pa_group_unit', ['group_id' => 1]);
    }
}
