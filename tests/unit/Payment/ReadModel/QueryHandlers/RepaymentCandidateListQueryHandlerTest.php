<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Mockery;
use Model\Common\Repositories\IParticipantRepository;
use Model\Common\Services\QueryBus;
use Model\DTO\Participant\Participant;
use Model\DTO\Payment\Payment;
use Model\DTO\Payment\RepaymentCandidate;
use Model\Event\SkautisCampId;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\Payment\ReadModel\Queries\RepaymentCandidateListQuery;
use Model\Payment\Repositories\IGroupRepository;

use function array_map;
use function array_sum;
use function count;

final class RepaymentCandidateListQueryHandlerTest extends Unit
{
    private const GROUP_ID = 1;
    private const CAMP_ID  = 7;

    public function test(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static function (PaymentListQuery $query): bool {
                return $query->getGroupId() === self::GROUP_ID;
            })
            ->andReturn([
                $this->createPayment(State::CANCELED, 100, 100, null),
                $this->createPayment(State::COMPLETED, 123, 200.0, null),
                $this->createPayment(State::COMPLETED, 124, 500.0, '2000145399/0800'),
                $this->createPayment(State::COMPLETED, null, 600.0, null),
            ]);

        $groups = Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('find')
            ->once()
            ->withArgs(static function (int $groupId): bool {
                return $groupId === self::GROUP_ID;
            })->andReturn(
                Mockery::mock(Group::class, ['getObject' => new SkautisEntity(self::CAMP_ID, Type::CAMP())]),
            );

        $participants = Mockery::mock(IParticipantRepository::class);
        $participants->shouldReceive('findByCamp')
            ->once()
            ->withArgs(static function (SkautisCampId $campId): bool {
                return $campId->toInt() === self::CAMP_ID;
            })
            ->andReturn([
                $this->createParticipant(100, 20.0),
                $this->createParticipant(123, 12),
                $this->createParticipant(124, 18),
                $this->createParticipant(208, 1000),
            ]);

        $handler = new RepaymentCandidateListQueryHandler($queryBus, $groups, $participants);

        $repaymentCandidates = $handler(new RepaymentCandidateListQuery(self::GROUP_ID));

        //total count of candidates
        $this->assertSame(3, count($repaymentCandidates));

        //total amount of suggested repayments
        $this->assertSame(630.0, array_sum(array_map(fn (RepaymentCandidate $candidate) => $candidate->getAmount(), $repaymentCandidates)));
    }

    private function createPayment(string $state, ?int $personId, float $amount, ?string $bankAccount): Payment
    {
        return new Payment(
            1,
            'My Name',
            $amount,
            [],
            Date::create(2021, 06, 17),
            null,
            null,
            '',
            $state !== State::PREPARING,
            State::get($state),
            $bankAccount !== null ? new Transaction('123', $bankAccount, '', null) : null,
            $state !== State::PREPARING ? new DateTimeImmutable() : null,
            null,
            $personId,
            1,
            [],
        );
    }

    private function createParticipant(int $personId, float $repayment): Participant
    {
        return Mockery::mock(Participant::class, [
            'getPersonId' => $personId,
            'getRepayment' => $repayment,
        ]);
    }
}
