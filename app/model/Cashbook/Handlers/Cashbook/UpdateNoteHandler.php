<?php

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateNote;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateNoteHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;


    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    /**
     * @throws \Model\Cashbook\CashbookNotFoundException
     */
    public function handle(UpdateNote $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $cashbook->updateNote($command->getNote());
        $this->cashbooks->save($cashbook);
    }

}
