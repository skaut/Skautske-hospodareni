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
    private IParticipantRepository $participants;

    private IPaymentRepository $payments;

    public function __construct(IParticipantRepository $participants, IPaymentRepository $payments)
    {
        $this->participants = $participants;
        $this->payments     = $payments;
    }

    public function __invoke(RemoveCampParticipant $command) : void
    {
        try {
            $this->payments->remove($this->payments->findByParticipant($command->getParticipantId(), EventType::CAMP()));
        } catch (PaymentNotFound $exc) {
        }

        $this->participants->removeCampParticipant($command->getParticipantId());
    }
}
