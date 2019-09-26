<?php

declare(strict_types=1);

namespace Model\Common\Repositories;

use Model\DTO\Participant\Participant;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;

interface IParticipantRepository
{
    /**
     * @return Participant[]
     */
    public function findByEvent(SkautisEventId $id) : array;

    /**
     * @return Participant[]
     */
    public function findByCamp(SkautisCampId $id) : array;
}
