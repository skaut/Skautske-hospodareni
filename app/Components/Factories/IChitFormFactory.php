<?php

declare(strict_types=1);

namespace App\Components\Factories;

use App\Components\ChitForm;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Common\UnitId;

interface IChitFormFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable, UnitId $unitId): ChitForm;
}
