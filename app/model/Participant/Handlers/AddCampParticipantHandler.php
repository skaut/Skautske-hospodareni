<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\AddCampParticipant;
use Model\Common\Repositories\IParticipantRepository;

final class AddCampParticipantHandler
{
    private IParticipantRepository $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    public function __invoke(AddCampParticipant $command) : void
    {
        $this->participants->addCampParticipant($command->getCampId(), $command->getPersonId());
    }
}
