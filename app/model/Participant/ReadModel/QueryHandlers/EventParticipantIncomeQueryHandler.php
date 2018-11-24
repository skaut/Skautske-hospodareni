<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Participant\EventType;
use Model\Participant\Participant;
use Model\Participant\Repositories\IParticipantRepository;

class EventParticipantIncomeQueryHandler
{
    /** @var IParticipantRepository */
    private $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    public function handle(EventParticipantIncomeQuery $query) : float
    {
        $participants = $this->participants->findByEvent(
            EventType::GENERAL(),
            $query->getEventId()->toInt()
        );

        $participantIncome = 0.0;
        /** @var Participant $p */
        foreach ($participants as $p) {
            $participantIncome += $p->getPayment() !== null ? (float) $p->getPayment()->getAmount() : 0;
        }

        return $participantIncome;
    }
}
