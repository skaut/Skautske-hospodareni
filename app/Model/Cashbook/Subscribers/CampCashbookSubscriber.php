<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Subscribers;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use App\Model\Cashbook\Events\ChitWasAdded;
use App\Model\Cashbook\Events\ChitWasUpdated;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use LogicException;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * Update category total in Skautis for camp cashbook.
 */
final class CampCashbookSubscriber implements MessageSubscriberInterface
{
    public function __construct(private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    /** @return array<string, mixed> */
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

        if (! $cashbook instanceof Cashbook) {
            throw new LogicException('Assertion failed.');
        }

        return $cashbook->getType()->equalsValue(CashbookType::CAMP);
    }
}
