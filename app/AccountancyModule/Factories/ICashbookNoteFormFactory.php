<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\NoteForm;
use Model\Cashbook\Cashbook\CashbookId;

interface ICashbookNoteFormFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable): NoteForm;
}
