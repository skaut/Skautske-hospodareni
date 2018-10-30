<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Participant\Participant;
use Model\Skautis\Factory\ParticipantFactory;
use Skautis\Skautis;
use function array_map;

class EventParticipantIncomeQueryHandler
{
    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function handle(EventParticipantIncomeQuery $query) : float
    {
        $participants = (array) $this->skautis->event->ParticipantGeneralAll(['ID_EventGeneral' => $query->getEventId()->toInt()]);
        $participants = array_map([ParticipantFactory::class, 'create'], $participants);

        $participantIncome = 0.0;
        /** @var Participant $p */
        foreach ($participants as $p) {
            $participantIncome += $p->getPayment() !== null ? (float) $p->getPayment()->getAmount() : 0;
        }

        return $participantIncome;
    }
}
