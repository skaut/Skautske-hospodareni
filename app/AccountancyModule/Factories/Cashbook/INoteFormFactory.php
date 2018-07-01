<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\NoteForm;
use Model\Cashbook\Cashbook\CashbookId;

interface INoteFormFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable): NoteForm;

}
