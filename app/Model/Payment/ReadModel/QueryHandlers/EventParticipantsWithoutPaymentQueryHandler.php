<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use App\Model\Event\SkautisEventId;
use App\Model\Payment\Group\Type;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use App\Model\Payment\Repositories\IGroupRepository;
use Assert\Assertion;

use function array_filter;
use function array_values;
use function in_array;

final class EventParticipantsWithoutPaymentQueryHandler
{
    public function __construct(private IGroupRepository $groups, private PaymentService $paymentService, private QueryBus $queryBus)
    {
    }

    /** @return Participant[] */
    public function __invoke(EventParticipantsWithoutPaymentQuery $query): array
    {
        $skautisEntity = $this->groups->find($query->getGroupId())->getObject();

        Assertion::notNull($skautisEntity);
        Assertion::same(Type::EVENT, $skautisEntity->getType()->getValue());

        $participants = $this->queryBus->handle(
            new EventParticipantListQuery(new SkautisEventId($skautisEntity->getId())),
        );

        $ignoredPersonIds = $this->paymentService->getPersonsWithActivePayment($query->getGroupId());

        $participants = array_filter(
            $participants,
            function (Participant $p) use ($ignoredPersonIds) {
                return ! in_array($p->getPersonId(), $ignoredPersonIds, true);
            },
        );

        return array_values($participants);
    }
}
