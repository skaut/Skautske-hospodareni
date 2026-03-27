<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\RemoveEventParticipant;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentNotFound;
use App\Model\Participant\Repositories\IPaymentRepository;

final class RemoveEventParticipantHandler
{
    public function __construct(private IParticipantRepository $participants, private IPaymentRepository $payments)
    {
    }

    public function __invoke(RemoveEventParticipant $command): void
    {
        try {
            $this->payments->remove($this->payments->findByParticipant($command->getParticipantId(), EventType::GENERAL()));
        } catch (PaymentNotFound) {
        }

        $this->participants->removeEventParticipant($command->getParticipantId());
    }
}
