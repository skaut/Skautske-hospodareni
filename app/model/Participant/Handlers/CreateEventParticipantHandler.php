<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\CreateEventParticipant;
use Model\Common\Repositories\IParticipantRepository;

final class CreateEventParticipantHandler
{
    private IParticipantRepository $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    public function __invoke(CreateEventParticipant $command) : void
    {
        $this->participants->createEventParticipant($command->getEventId(), $command->getParticipant());
    }
}
