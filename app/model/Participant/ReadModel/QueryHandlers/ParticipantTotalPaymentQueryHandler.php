<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\ParticipantTotalPaymentQuery;
use Model\DTO\Participant\Participant;
use Model\Event\SkautisEventId;
use function array_reduce;

final class ParticipantTotalPaymentQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(ParticipantTotalPaymentQuery $query) : float
    {
        return (float) array_reduce(
            $this->queryBus->handle(
                $query->getId() instanceof SkautisEventId
                    ? new EventParticipantListQuery($query->getId())
                    : new CampParticipantListQuery($query->getId())
            ),
            function ($res, Participant $v) {
                return $res + $v->getPayment();
            }
        );
    }
}
