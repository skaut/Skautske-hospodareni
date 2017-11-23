<?php

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateChitHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(UpdateChit $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());


        $cashbook->updateChit(
            $command->getChitId(),
            $command->getNumber(),
            $command->getDate(),
            $command->getRecipient(),
            $command->getAmount(),
            $command->getPurpose(),
            $command->getCategoryId()
        );

        $this->cashbooks->save($cashbook);
    }

}
