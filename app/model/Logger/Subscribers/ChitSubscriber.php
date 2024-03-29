<?php

declare(strict_types=1);

namespace Model\Logger\Subscribers;

use Model\Chit\Events\ChitWasRemoved;
use Model\Chit\Events\ChitWasUpdated;
use Model\Logger\Log\Type;
use Model\LoggerService;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

final class ChitSubscriber implements MessageSubscriberInterface
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $ls)
    {
        $this->loggerService = $ls;
    }

    /** @return array<string, mixed> */
    public static function getHandledMessages(): array
    {
        return [
            ChitWasUpdated::class => ['method' => 'handleUpdate'],
            ChitWasRemoved::class => ['method' => 'handleRemove'],
        ];
    }

    public function handleUpdate(ChitWasUpdated $chit): void
    {
        $this->loggerService->log(
            $chit->getUnitId(),
            $chit->getUserId(),
            "Uživatel '" . $chit->getUserName() . "' upravil paragon (ID=" . $chit->getChitId() . ').',
            Type::get(Type::OBJECT),
            $chit->getEventId(),
        );
    }

    public function handleRemove(ChitWasRemoved $chit): void
    {
        $this->loggerService->log(
            $chit->getUnitId(),
            $chit->getUserId(),
            "Uživatel '" . $chit->getUserName() . "' odebral paragon (ID=" . $chit->getChitId() . ', účel=' . $chit->getChitPurpose() . ').',
            Type::get(Type::OBJECT),
            $chit->getEventId(),
        );
    }
}
