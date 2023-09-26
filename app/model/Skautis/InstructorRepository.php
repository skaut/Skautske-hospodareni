<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Common\Repositories\IInstructorRepository;
use Model\DTO\Instructor\Instructor as InstructorDTO;
use Model\DTO\Payment\InstructorFactory as InstructorDTOFactory;
use Model\Event\SkautisEducationId;
use Model\Instructor\Instructor;
use Model\Participant\Payment\Event;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentFactory;
use Model\Participant\Repositories\IPaymentRepository;
use Model\Skautis\Factory\InstructorFactory;
use Skautis\Skautis;
use stdClass;

use function array_diff_key;
use function array_key_exists;
use function array_map;
use function is_array;
use function strcoll;
use function usort;

final class InstructorRepository implements IInstructorRepository
{
    public function __construct(private Skautis $skautis, private IPaymentRepository $payments)
    {
    }

    /** @return InstructorDTO[] */
    public function findByEducation(SkautisEducationId $id): array
    {
        $instructors = $this->skautis->event->InstructorAll(['ID_EventEducation' => $id->toInt()]);
        if (! is_array($instructors)) {
            return []; // API returns empty object when there are no results
        }

        $event = new Event($id->toInt(), EventType::EDUCATION());

        return $this->processInstructors($instructors, $event);
    }

    /**
     * @param stdClass[] $instructorsSis
     *
     * @return InstructorDTO[]
     */
    private function processInstructors(array $instructorsSis, Event $event): array
    {
        $eventPayments = $this->payments->findByEvent($event);
        $instructors   = [];
        foreach ($instructorsSis as $p) {
            if (array_key_exists($p->ID, $eventPayments)) {
                $payment =  $eventPayments[$p->ID];
            } else {
                $payment =  PaymentFactory::createDefault($p->ID, $event);
            }

            $instructors[$p->ID] = InstructorFactory::create($p, $payment); // TODO
        }

        foreach (array_diff_key($eventPayments, $instructors) as $paymentToRemove) {
            $this->payments->remove($paymentToRemove); //delete zaznam, protoze neexistuje k nemu ucastnik
        }

        usort(
            $instructors,
            fn (Instructor $one, Instructor $two) => strcoll($one->getDisplayName(), $two->getDisplayName())
        );

        return array_map([InstructorDTOFactory::class, 'create'], $instructors);
    }
}
