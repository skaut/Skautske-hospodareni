<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\MoveChitsToDifferentCashbook;
use App\Model\Cashbook\Repositories\CategoryRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;

final class MoveChitsToDifferentCashbookHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private ?CategoryRepository $categories = null)
    {
    }

    public function __invoke(MoveChitsToDifferentCashbook $command): void
    {
        $sourceCashbook = $this->cashbooks->find($command->getSourceCashbookId());
        $targetCashbook = $this->cashbooks->find($command->getTargetCashbookId());

        $sourceCategories = $this->categories?->findForCashbook($sourceCashbook->getId(), $sourceCashbook->getType());
        $targetCategories = $this->categories?->findForCashbook($targetCashbook->getId(), $targetCashbook->getType());
        $targetCashbook->copyChitsFrom($command->getChitIds(), $sourceCashbook, $sourceCategories, $targetCategories);

        foreach ($command->getChitIds() as $chitId) {
            $sourceCashbook->removeChit($chitId);
        }

        $this->cashbooks->save($targetCashbook);
        $this->cashbooks->save($sourceCashbook);
    }
}
