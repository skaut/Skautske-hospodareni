<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\AddEventParticipant;
use Model\Common\Repositories\IParticipantRepository;

final class AddEventParticipantHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    public function __invoke(AddEventParticipant $command): void
    {
        $this->participants->addEventParticipant($command->getEventId(), $command->getPersonId());
    }
}
