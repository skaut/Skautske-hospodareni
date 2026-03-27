<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Commands\Cashbook\UpdateNote;
use App\Model\Cashbook\Repositories\ICashbookRepository;

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
