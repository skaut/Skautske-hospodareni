<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\CreateCampParticipant;
use Model\Common\Repositories\IParticipantRepository;

final class CreateCampParticipantHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    public function __invoke(CreateCampParticipant $command): void
    {
        $this->participants->createCampParticipant($command->getCampId(), $command->getParticipant());
    }
}
