<?php

declare(strict_types=1);

namespace Model\Event\Repositories;

use Model\Event\Camp;
use Model\Event\Exception\CampNotFound;
use Model\Event\SkautisCampId;

interface ICampRepository
{
    /**
     * @throws CampNotFound
     */
    public function find(SkautisCampId $id) : Camp;
}
