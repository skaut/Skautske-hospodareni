<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Repositories\IMemberRepository;
use App\Model\DTO\Payment\Person;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use App\Model\Payment\Repositories\IMemberEmailRepository;

use function in_array;

final class MembersWithoutPaymentInGroupQueryHandler
{
    public function __construct(
        private IMemberRepository $members,
        private IMemberEmailRepository $emails,
        private PaymentService $paymentService,
    ) {
    }

    /** @return Person[] */
    public function __invoke(MembersWithoutPaymentInGroupQuery $query): array
    {
        $personsWithPayment = $this->paymentService->getPersonsWithActivePayment($query->getGroupId());

        $persons = [];

        foreach ($this->members->findByUnit($query->getUnitId(), ! $query->isDirectMemberOnly()) as $member) {
            if (in_array($member->getId(), $personsWithPayment, true)) {
                continue;
            }

            $persons[] = new Person(
                $member->getId(),
                $member->getName(),
                $this->emails->findByMember($member->getId()),
            );
        }

        return $persons;
    }
}
