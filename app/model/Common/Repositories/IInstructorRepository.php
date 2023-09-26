<?php

declare(strict_types=1);

namespace Model\Common\Repositories;

use Model\DTO\Instructor\Instructor;
use Model\Event\SkautisEducationId;

interface IInstructorRepository
{
    /** @return Instructor[] */
    public function findByEducation(SkautisEducationId $id): array;
}
