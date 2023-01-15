<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\RemoveCampParticipant;
use Model\Common\Repositories\IParticipantRepository;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentNotFound;
use Model\Participant\Repositories\IPaymentRepository;

final class RemoveCampParticipantHandler
{
    public function __construct(private IParticipantRepository $participants, private IPaymentRepository $payments)
    {
    }

    public function __invoke(RemoveCampParticipant $command): void
    {
        try {
            $this->payments->remove($this->payments->findByParticipant($command->getParticipantId(), EventType::CAMP()));
        } catch (PaymentNotFound) {
        }

        $this->participants->removeCampParticipant($command->getParticipantId());
    }
}
