<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\RemoveEventParticipant;
use Model\Common\Repositories\IParticipantRepository;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentNotFound;
use Model\Participant\Repositories\IPaymentRepository;

final class RemoveEventParticipantHandler
{
    /** @var IParticipantRepository */
    private $participants;

    /** @var IPaymentRepository */
    private $payments;

    public function __construct(IParticipantRepository $participants, IPaymentRepository $payments)
    {
        $this->participants = $participants;
        $this->payments     = $payments;
    }

    public function __invoke(RemoveEventParticipant $command) : void
    {
        try {
            $this->payments->remove($this->payments->findByParticipant($command->getParticipantId(), EventType::GENERAL()));
        } catch (PaymentNotFound $exc) {
        }

        $this->participants->removeEventParticipant($command->getParticipantId());
    }
}
