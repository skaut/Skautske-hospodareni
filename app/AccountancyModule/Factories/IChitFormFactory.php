<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\ChitForm;

interface IChitFormFactory
{

    public function create(int $cashbookId, bool $isEditable): ChitForm;

}
