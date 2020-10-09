<?php

declare(strict_types=1);

namespace Model;

use InvalidArgumentException;
use Model\DTO\Participant\UpdateParticipant;
use Model\Participant\Payment\Event;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentFactory;
use Model\Participant\PaymentNotFound;
use Model\Participant\Repositories\IPaymentRepository;
use Model\Utils\MoneyFactory;
use Skautis\Skautis;
use function sprintf;

class ParticipantService extends MutableBaseService
{
    private IPaymentRepository $repository;

    public function __construct(string $name, Skautis $skautIS, IPaymentRepository $repository)
    {
        parent::__construct($name, $skautIS);
        $this->repository = $repository;
    }

    public function update(UpdateParticipant $updateParticipant) : void
    {
        if ($updateParticipant->getField() === 'days') {
            $sisData = [
                'ID' => $updateParticipant->getParticipantId(),
                'Real' => true,
                'Days' => $updateParticipant->getValue(),
            ];
            $this->skautis->event->{'Participant' . $this->typeName . 'Update'}($sisData, 'participant' . $this->typeName);

            return;
        }

        $event = new Event($updateParticipant->getEventId(), $this->type === EventType::CAMP ? EventType::CAMP() : EventType::GENERAL());
        try {
            $payment = $this->repository->findByParticipant($updateParticipant->getParticipantId(), $event->getType());
        } catch (PaymentNotFound $exc) {
            $payment = PaymentFactory::createDefault($updateParticipant->getParticipantId(), $event);
        }

        switch ($updateParticipant->getField()) {
            case UpdateParticipant::FIELD_PAYMENT:
                $payment->setPayment(MoneyFactory::fromFloat((float) $updateParticipant->getValue()));
                break;
            case UpdateParticipant::FIELD_REPAYMENT:
                $payment->setRepayment(MoneyFactory::fromFloat((float) $updateParticipant->getValue()));
                break;
            case UpdateParticipant::FIELD_IS_ACCOUNT:
                $payment->setAccount($updateParticipant->getValue());
                break;
            default:
                throw new InvalidArgumentException(sprintf("Camp participant hasn't attribute '%s'", $updateParticipant->getField()));
        }

        $this->repository->save($payment);
    }
}
