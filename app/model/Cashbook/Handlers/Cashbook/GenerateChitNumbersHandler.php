<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Commands\Cashbook\GenerateChitNumbers;
use Model\Cashbook\MaxChitNumberNotFound;
use Model\Cashbook\NonNumericChitNumbers;
use Model\Cashbook\Repositories\ICashbookRepository;

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
