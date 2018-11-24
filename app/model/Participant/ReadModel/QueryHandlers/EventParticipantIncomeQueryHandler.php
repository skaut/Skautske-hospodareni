<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Participant\EventType;
use Model\Participant\Participant;
use Model\Participant\Repositories\IParticipantRepository;
use Model\Utils\MoneyFactory;
use Money\Money;

class EventParticipantIncomeQueryHandler
{
    /** @var IParticipantRepository */
    private $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    public function handle(EventParticipantIncomeQuery $query) : Money
    {
        $participants = $this->participants->findByEvent(
            EventType::GENERAL(),
            $query->getEventId()->toInt()
        );

        $participantIncome = MoneyFactory::zero();

        /** @var Participant $p */
        foreach ($participants as $p) {
            $participantIncome = $participantIncome->add($p->getPayment());
        }

        return $participantIncome;
    }
}
