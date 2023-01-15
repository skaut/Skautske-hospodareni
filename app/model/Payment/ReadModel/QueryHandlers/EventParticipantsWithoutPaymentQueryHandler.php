<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Assert\Assertion;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Participant\Participant;
use Model\Event\SkautisEventId;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use Model\Payment\Repositories\IGroupRepository;
use Model\PaymentService;

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
