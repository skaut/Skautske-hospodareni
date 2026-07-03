<?php

declare(strict_types=1);

namespace App\Components\Factories\Cashbook;

use App\Components\NoteForm;
use App\Model\Cashbook\Cashbook\CashbookId;

interface INoteFormFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable): NoteForm;
}
