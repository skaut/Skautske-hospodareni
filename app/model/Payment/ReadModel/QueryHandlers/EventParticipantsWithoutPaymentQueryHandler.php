<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Assert\Assertion;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
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
    /** @var IGroupRepository */
    private $groups;

    /** @var PaymentService */
    private $paymentService;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(IGroupRepository $groups, PaymentService $paymentService, QueryBus $queryBus)
    {
        $this->groups         = $groups;
        $this->paymentService = $paymentService;
        $this->queryBus       = $queryBus;
    }

    /**
     * @return Participant[]
     */
    public function __invoke(EventParticipantsWithoutPaymentQuery $query) : array
    {
        $skautisEntity = $this->groups->find($query->getGroupId())->getObject();

        Assertion::notNull($skautisEntity);
        Assertion::same(Type::EVENT, $skautisEntity->getType()->getValue());

        $participants = $this->queryBus->handle(
            new EventParticipantListQuery(new SkautisEventId($skautisEntity->getId()))
        );

        $ignoredPersonIds = $this->paymentService->getPersonsWithActivePayment($query->getGroupId());

        $participants = array_filter(
            $participants,
            function (Participant $p) use ($ignoredPersonIds) {
                return ! in_array($p->getPersonId(), $ignoredPersonIds, true);
            }
        );

        return array_values($participants);
    }
}
