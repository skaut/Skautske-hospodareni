<?php

declare(strict_types=1);

namespace Model\Cashbook\Subscribers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

use function assert;

/**
 * Update category total in Skautis for camp cashbook
 */
final class CampCashbookSubscriber implements MessageSubscriberInterface
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getHandledMessages(): array
    {
        return [
            ChitWasAdded::class => ['method' => 'chitWasAdded'],
            ChitWasUpdated::class => ['method' => 'chitWasUpdated'],
        ];
    }

    public function chitWasAdded(ChitWasAdded $event): void
    {
        $id = $event->getCashbookId();

        if (! $this->isCamp($id)) {
            return;
        }

        $this->updateCategories($id);
    }

    public function chitWasUpdated(ChitWasUpdated $event): void
    {
        $id = $event->getCashbookId();

        if (! $this->isCamp($id)) {
            return;
        }

        $this->updateCategories($id);
    }

    private function updateCategories(CashbookId $cashbookId): void
    {
        $this->commandBus->handle(new UpdateCampCategoryTotals($cashbookId));
    }

    private function isCamp(CashbookId $cashbookId): bool
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getType()->equalsValue(CashbookType::CAMP);
    }
}
