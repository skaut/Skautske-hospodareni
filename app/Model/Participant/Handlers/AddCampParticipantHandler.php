<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\AddCampParticipant;
use App\Model\Common\Repositories\IParticipantRepository;

final class AddCampParticipantHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    public function __invoke(AddCampParticipant $command): void
    {
        $this->participants->addCampParticipant($command->getCampId(), $command->getPersonId());
    }
}
