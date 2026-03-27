<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\CreateEventParticipant;
use App\Model\Common\Repositories\IParticipantRepository;

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
