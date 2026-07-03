<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\CreateCampParticipant;
use App\Model\Common\Repositories\IParticipantRepository;

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
