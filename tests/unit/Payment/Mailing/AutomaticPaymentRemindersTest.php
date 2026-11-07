<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Payment\Group;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\TaskQueue\Repository\TaskQueueRepository;
use App\Model\TaskQueue\TaskQueue;
use Codeception\Test\Unit as TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function str_contains;

final class AutomaticPaymentRemindersTest extends TestCase
{
    protected function _after(): void
    {
        Mockery::close();
    }

    public function testFailureOfOneGroupDoesNotStopTheOthers(): void
    {
        $payments = Mockery::mock(IPaymentRepository::class);
        $payments->shouldReceive('findDueAutomaticReminders')->with(1, 3, Mockery::any())->once()->andThrow(new RuntimeException('Broken group.'));
        $payments->shouldReceive('findDueAutomaticReminders')->with(2, 3, Mockery::any())->once()->andReturn([]);
        $logger = $this->logger();

        $this->reminders($payments, $logger, entityManagerOpen: true)->queueDue();

        $logger->shouldHaveReceived('error')->with(Mockery::on(static fn (string $message): bool => str_contains($message, 'group #1')), Mockery::any())->once();
    }

    /** A failed transaction closes the entity manager, so the remaining groups wait for the next run. */
    public function testClosedEntityManagerStopsTheRun(): void
    {
        $payments = Mockery::mock(IPaymentRepository::class);
        $payments->shouldReceive('findDueAutomaticReminders')->with(1, 3, Mockery::any())->once()->andThrow(new RuntimeException('Broken group.'));
        $payments->shouldNotReceive('findDueAutomaticReminders')->with(2, 3, Mockery::any());
        $logger = $this->logger();

        $this->reminders($payments, $logger, entityManagerOpen: false)->queueDue();

        $logger->shouldHaveReceived('error')->once();
    }

    /** @return LoggerInterface&MockInterface */
    private function logger(): LoggerInterface
    {
        return Mockery::spy(LoggerInterface::class);
    }

    private function reminders(IPaymentRepository $payments, LoggerInterface $logger, bool $entityManagerOpen): AutomaticPaymentReminders
    {
        $groups = Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('findSendingAutomaticReminders')->andReturn([$this->group(1), $this->group(2)]);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('isOpen')->andReturn($entityManagerOpen);

        $emailQueue = new PaymentEmailQueue(
            new TaskQueue(new TaskQueueRepository($entityManager), $entityManager),
            $payments,
            $groups,
            Mockery::mock(IUserRepository::class),
            $entityManager,
        );

        return new AutomaticPaymentReminders($groups, $payments, $emailQueue, $entityManager, $logger);
    }

    private function group(int $id): Group
    {
        $group = Mockery::mock(Group::class);
        $group->shouldReceive('getId')->andReturn($id);
        $group->shouldReceive('getReminderDaysAfterDue')->andReturn(3);

        return $group;
    }
}
