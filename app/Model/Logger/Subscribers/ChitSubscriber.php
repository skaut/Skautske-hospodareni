<?php

declare(strict_types=1);

namespace App\Model\Logger\Subscribers;

use App\Model\Chit\Events\ChitWasRemoved;
use App\Model\Chit\Events\ChitWasUpdated;
use App\Model\Logger\Log\Type;
use App\Model\Logger\LoggerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class ChitSubscriber
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $ls)
    {
        $this->loggerService = $ls;
    }

    #[AsMessageHandler]
    public function handleUpdate(ChitWasUpdated $chit): void
    {
        $this->loggerService->log(
            $chit->getUnitId(),
            $chit->getUserId(),
            "Uživatel '".$chit->getUserName()."' upravil paragon (ID=".$chit->getChitId().').',
            Type::get(Type::OBJECT),
            $chit->getEventId(),
        );
    }

    #[AsMessageHandler]
    public function handleRemove(ChitWasRemoved $chit): void
    {
        $this->loggerService->log(
            $chit->getUnitId(),
            $chit->getUserId(),
            "Uživatel '".$chit->getUserName()."' odebral paragon (ID=".$chit->getChitId().', účel='.$chit->getChitPurpose().').',
            Type::get(Type::OBJECT),
            $chit->getEventId(),
        );
    }
}
