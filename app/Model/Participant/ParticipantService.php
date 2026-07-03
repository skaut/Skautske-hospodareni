<?php

declare(strict_types=1);

namespace App\Model\Participant;

use App\Model\DTO\Participant\UpdateParticipant;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\Repositories\IPaymentRepository;
use App\Model\Utils\MoneyFactory;
use InvalidArgumentException;
use Skautis\Skautis;

use function sprintf;
use function ucfirst;

final class ParticipantService
{
    public function __construct(private Skautis $skautis, private IPaymentRepository $repository)
    {
    }

    public function update(EventType $eventType, UpdateParticipant $updateParticipant): void
    {
        if ($updateParticipant->getField() === 'days') {
            $typeName = ucfirst($eventType->toString());
            $sisData = [
                'ID' => $updateParticipant->getParticipantId(),
                'Real' => true,
                'Days' => $updateParticipant->getValue(),
                'IsAccepted' => $updateParticipant->isAccepted(),
            ];
            $this->skautis->event->{'Participant'.$typeName.'Update'}($sisData, 'participant'.$typeName);

            return;
        }

        $event = new Event($updateParticipant->getEventId(), $eventType);
        try {
            $payment = $this->repository->findByParticipant($updateParticipant->getParticipantId(), $event->getType());
        } catch (PaymentNotFound) {
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
