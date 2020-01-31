<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\MissingAutocomputedCategoryControl;
use Model\Event\SkautisCampId;

interface IMissingAutocomputedCategoryControlFactory
{
    public function create(SkautisCampId $campId) : MissingAutocomputedCategoryControl;
}
