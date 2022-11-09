<?php

declare(strict_types=1);

namespace Model\Event\Repositories;

use Model\Event\Education;
use Model\Event\Exception\EducationNotFound;
use Model\Event\SkautisEducationId;

interface IEducationRepository
{
    /** @throws EducationNotFound */
    public function find(SkautisEducationId $id): Education;
}
