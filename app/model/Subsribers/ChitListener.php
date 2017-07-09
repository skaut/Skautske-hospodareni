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
            $ch->getUnitId(),
            $ch->getUserId(),
            "Uživatel '" . $ch->getUserUserName() . "' upravil paragon (ID=" . $ch->getChitId() . ").",
            Type::get(Type::OBJECT),
            $ch->getLocalId()
        );
    }

    public function handleRemove(ChitWasRemoved $ch): void
    {
        $this->loggerService->log(
            $ch->getUnitId(),
            $ch->getUserId(),
            "Uživatel '" . $ch->getUserUserName() . "' odebral paragon (ID=" . $ch->getChitId() . ", účel=" . $ch->getChitPurpose() . ").",
            Type::get(Type::OBJECT),
            $ch->getLocalId()
        );
    }


}
