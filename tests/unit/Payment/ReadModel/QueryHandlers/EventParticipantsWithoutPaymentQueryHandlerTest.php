<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Assert\InvalidArgumentException;
use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\DTO\Participant\Participant;
use Model\Payment\Group;
use Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use Model\Payment\Repositories\IGroupRepository;
use Model\PaymentService;

final class EventParticipantsWithoutPaymentQueryHandlerTest extends Unit
{
    private const GROUP_ID = 10;
    private const EVENT_ID = 50;

    public function test() : void
    {
        $paymentService = \Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('getPersonsWithActivePayment')
            ->once()
            ->withArgs([self::GROUP_ID])
            ->andReturn([1, 3]);

        $groupRepository = $this->mockGroupRepository(
            new Group\SkautisEntity(self::EVENT_ID, Group\Type::get(Group\Type::EVENT))
        );

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(function (EventParticipantListQuery $query) : bool {
                return $query->getEventId()->toInt() === self::EVENT_ID;
            })->andReturn([
                \Mockery::mock(Participant::class, ['getPersonId' => 1]),
                \Mockery::mock(Participant::class, ['getPersonId' => 2]),
                \Mockery::mock(Participant::class, ['getPersonId' => 3]),
                \Mockery::mock(Participant::class, ['getPersonId' => 4]),
            ]);

        $handler = new EventParticipantsWithoutPaymentQueryHandler($groupRepository, $paymentService, $queryBus);

        $participants = $handler(new EventParticipantsWithoutPaymentQuery(self::GROUP_ID));

        $this->assertCount(2, $participants);
        $this->assertSame(2, $participants[0]->getPersonId());
        $this->assertSame(4, $participants[1]->getPersonId());
    }

    /**
     * @dataProvider dataInvalidSkautisEntities
     */
    public function testThrowsExceptionIfGroupIsNotEventGroup(?Group\SkautisEntity $skautisEntity) : void
    {
        $handler = new EventParticipantsWithoutPaymentQueryHandler(
            $this->mockGroupRepository($skautisEntity),
            \Mockery::mock(PaymentService::class),
            new QueryBus()
        );

        $this->expectException(InvalidArgumentException::class);

        $handler(new EventParticipantsWithoutPaymentQuery(self::GROUP_ID));
    }

    /**
     * @return (SkautisEntity|null)[][]
     */
    public static function dataInvalidSkautisEntities() : array
    {
        return [
            [new Group\SkautisEntity(10, Group\Type::get(Group\Type::CAMP))],
            [null],
        ];
    }

    private function mockGroupRepository(?Group\SkautisEntity $skautisEntity) : IGroupRepository
    {
        $groups = \Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('find')
            ->once()
            ->withArgs([self::GROUP_ID])
            ->andReturn(
                \Mockery::mock(
                    Group::class,
                    ['getObject' => $skautisEntity]
                )
            );

        return $groups;
    }
}
