<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Commands\Cashbook\GenerateChitNumbers;
use App\Model\Cashbook\MaxChitNumberNotFound;
use App\Model\Cashbook\NonNumericChitNumbers;
use App\Model\Cashbook\Repositories\ICashbookRepository;

final class GenerateChitNumbersHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    /**
     * @throws MaxChitNumberNotFound
     * @throws CashbookNotFound
     * @throws NonNumericChitNumbers
     */
    public function __invoke(GenerateChitNumbers $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $cashbook->generateChitNumbers($command->getPaymentMethod());
        $this->cashbooks->save($cashbook);
    }
}
