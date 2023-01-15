<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\CreateEventParticipant;
use Model\Common\Repositories\IParticipantRepository;

final class CreateEventParticipantHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    public function __invoke(CreateEventParticipant $command): void
    {
        $this->participants->createEventParticipant($command->getEventId(), $command->getParticipant());
    }
}
