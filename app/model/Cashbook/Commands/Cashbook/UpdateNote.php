<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\UpdateNoteHandler;

/** @see UpdateNoteHandler */
final class UpdateNote
{
    public function __construct(private CashbookId $cashbookId, private string $note)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getNote(): string
    {
        return $this->note;
    }
}
