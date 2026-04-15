<?php

declare(strict_types=1);

namespace App\Components\Factories\Camps;

use App\Components\Camps\MissingAutocomputedCategoryControl;
use App\Model\Event\SkautisCampId;

interface IMissingAutocomputedCategoryControlFactory
{
    public function create(SkautisCampId $campId): MissingAutocomputedCategoryControl;
}
