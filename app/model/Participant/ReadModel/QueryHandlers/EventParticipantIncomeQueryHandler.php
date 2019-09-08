<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\DTO\Participant\Participant;
use Model\IParticipantServiceFactory;
use Model\ParticipantService;

class EventParticipantIncomeQueryHandler
{
    /** @var ParticipantService */
    private $service;

    public function __construct(IParticipantServiceFactory $participantFactory)
    {
        $this->service = $participantFactory->create('General');
    }

    public function __invoke(EventParticipantIncomeQuery $query) : float
    {
        $participants = $this->service->getAll($query->getEventId()->toInt());

        $participantIncome = 0.0;
        /** @var Participant $p */
        foreach ($participants as $p) {
            $participantIncome += $p->getPayment();
        }

        return $participantIncome;
    }
}
