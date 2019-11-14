<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\CreateCampParticipant;
use Model\Common\Repositories\IParticipantRepository;

final class CreateCampParticipantHandler
{
    /** @var IParticipantRepository */
    private $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    public function __invoke(CreateCampParticipant $command) : void
    {
        $this->participants->createCampParticipant($command->getCampId(), $command->getParticipant());
    }
}
