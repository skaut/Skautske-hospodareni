<?php

namespace App\Model\Subscribers;

use Model\Chit\Events\ChitWasRemoved;
use Model\Chit\Events\ChitWasUpdated;
use Model\Logger\Log\Type;
use Model\LoggerService;

class ChitListener
{
    private $loggerService;

    public function __construct(LoggerService $ls)
    {
        $this->loggerService = $ls;
    }

    public function handleUpdate(ChitWasUpdated $chit): void
    {
        $this->loggerService->log(
            $chit->getUnitId(),
            $chit->getUserId(),
            "Uživatel '" . $chit->getUserUserName() . "' upravil paragon (ID=" . $chit->getChitId() . ").",
            Type::get(Type::OBJECT),
            $chit->getLocalId()
        );
    }

    public function handleRemove(ChitWasRemoved $chit): void
    {
        $this->loggerService->log(
            $chit->getUnitId(),
            $chit->getUserId(),
            "Uživatel '" . $chit->getUserUserName() . "' odebral paragon (ID=" . $chit->getChitId() . ", účel=" . $chit->getChitPurpose() . ").",
            Type::get(Type::OBJECT),
            $chit->getLocalId()
        );
    }


}
