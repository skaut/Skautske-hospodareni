<?php

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Cashbook;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class AddChitToCashbookHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(AddChitToCashbook $command): void
    {
        $cashbook = $this->getCashbook($command->getCashbookId());

        $cashbook->addChit(
            $command->getNumber(),
            $command->getDate(),
            $command->getRecipient(),
            $command->getAmount(),
            $command->getPurpose(),
            $command->getCategoryId()
        );

        $this->cashbooks->save($cashbook);
    }

    /**
     * @todo This is TEMPORARY, right now cashbook aggregates must be created on the fly as they are only used for adding chits
     */
    private function getCashbook(int $id): Cashbook
    {
        try {
            return $this->cashbooks->find($id);
        } catch (CashbookNotFoundException $e) {
            return new Cashbook($id);
        }
    }

}
