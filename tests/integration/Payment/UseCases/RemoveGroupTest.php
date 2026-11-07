<?php

declare(strict_types=1);

namespace App\Model\Payment\IntegrationTests;

use App\Model\Payment\Commands\Group\RemoveGroup;
use App\Model\Payment\Group;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\Handlers\Group\RemoveGroupHandler;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Utils\MoneyFactory;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class RemoveGroupTest extends IntegrationTest
{
    private IGroupRepository $groups;

    private IPaymentRepository $payments;

    private RemoveGroupHandler $handler;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Group::class,
            Payment::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__.'/RemoveGroupTest.neon']);

        parent::_before();

        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->payments = $this->tester->grabService(IPaymentRepository::class);
        $this->handler = $this->tester->grabService(RemoveGroupHandler::class);
    }

    public function test(): void
    {
        $group = $this->createClosedGroup();

        // just to make sure it got the right ID
        $this->assertSame(1, $group->getId());

        for ($i = 1; $i <= 3; ++$i) {
            $this->payments->save($this->createPayment($group, 'test'.$i));
        }

        ($this->handler)(new RemoveGroup(1));

        $this->assertEmpty($this->payments->findByGroup(1));

        $this->expectException(GroupNotFound::class);

        $this->groups->find(1);
    }

    /** The parts of a split payment point to the original one, which may be removed before them. */
    public function testGroupWithSplitPaymentIsRemoved(): void
    {
        $group = $this->createClosedGroup();
        $groupId = (int) $group->getId();
        $original = $this->createPayment($group, 'original');
        $this->payments->save($original);
        $this->payments->save($this->createPayment($group, 'first part', $original));
        $this->payments->save($this->createPayment($group, 'second part', $original));

        ($this->handler)(new RemoveGroup($groupId));

        $this->assertEmpty($this->payments->findByGroup($groupId));
        $this->tester->dontSeeInDatabase('pa_group', ['id' => $groupId]);
    }

    private function createClosedGroup(): Group
    {
        $group = new Group(
            [123],
            null,
            'test',
            Helpers::createEmptyPaymentDefaults(),
            new DateTimeImmutable(),
            Helpers::createEmails(),
            null,
            null,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );

        $group->close(''); // only closed groups can be removed

        $this->groups->save($group);

        return $group;
    }

    private function createPayment(Group $group, string $name, ?Payment $splitFrom = null): Payment
    {
        return new Payment($group, $name, [], MoneyFactory::fromDecimal('120.00'), Helpers::getValidDueDate(), null, null, null, '', $splitFrom);
    }
}
