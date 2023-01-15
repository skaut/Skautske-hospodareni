<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Commands\Cashbook\UpdateNote;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateNoteHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    /** @throws CashbookNotFound */
    public function __invoke(UpdateNote $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $cashbook->updateNote($command->getNote());
        $this->cashbooks->save($cashbook);
    }
}
