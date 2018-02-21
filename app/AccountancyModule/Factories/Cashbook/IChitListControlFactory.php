<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\ChitListControl;

interface IChitListControlFactory
{

    public function create(int $cashbookId, bool $isEditable): ChitListControl;

}
