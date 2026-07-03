<?php

declare(strict_types=1);

namespace App\Model\Event\Repositories;

use App\Model\Event\Education;
use App\Model\Event\Exception\EducationNotFound;
use App\Model\Event\SkautisEducationId;

interface IEducationRepository
{
    /** @throws EducationNotFound */
    public function find(SkautisEducationId $id): Education;
}
