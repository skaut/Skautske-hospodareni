<?php

namespace App\Model\Subscribers;

use App\AccountancyModule\EventModule\Commands\ChitWasRemoved;
use App\AccountancyModule\EventModule\Commands\ChitWasUpdated;
use Model\Logger\Log\Type;
use Model\LoggerService;

class ChitListener
{

    private $loggerService;

    public function __construct(LoggerService $ls)
    {
        $this->loggerService = $ls;
    }

    public function handleUpdate(ChitWasUpdated $ch): void
    {
        $this->loggerService->log(
            $ch->getEvent()["ID_Unit"],
            $ch->getUser()["ID"],
            "Uživatel '" . $ch->getUser()["Person"] . "' upravil paragon (ID=" . $ch->getChit()["id"] . ").",
            Type::get(Type::OBJECT),
            $ch->getEvent()["localId"]
        );
    }

    public function handleRemove(ChitWasRemoved $ch): void
    {
        $this->loggerService->log(
            $ch->getEvent()["ID_Unit"],
            $ch->getUser()["ID"],
            "Uživatel '" . $ch->getUser()["Person"] . "' odebral paragon (ID=" . $ch->getChit()["id"] . ", účel=".$ch->getChit()["purpose"].").",
            Type::get(Type::OBJECT),
            $ch->getEvent()["localId"]
        );
    }


}
