<?php

declare(strict_types=1);

namespace App\Model\Event\Repositories;

use App\Model\Event\Camp;
use App\Model\Event\Exception\CampNotFound;
use App\Model\Event\SkautisCampId;

interface ICampRepository
{
    /** @throws CampNotFound */
    public function find(SkautisCampId $id): Camp;
}
