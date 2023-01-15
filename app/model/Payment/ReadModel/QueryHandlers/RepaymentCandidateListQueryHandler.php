<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\Common\Repositories\IParticipantRepository;
use Model\Common\Services\QueryBus;
use Model\DTO\Payment as DTO;
use Model\Event\SkautisCampId;
use Model\Payment\Group\Type;
use Model\Payment\Payment\State;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\Payment\ReadModel\Queries\RepaymentCandidateListQuery;
use Model\Payment\Repositories\IGroupRepository;

use function array_column;
use function array_filter;
use function array_key_exists;
use function array_map;
use function assert;

final class RepaymentCandidateListQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IGroupRepository $groups, private IParticipantRepository $participants)
    {
    }

    /** @return DTO\RepaymentCandidate[] */
    public function __invoke(RepaymentCandidateListQuery $query): array
    {
        $payments   = array_filter(
            $this->queryBus->handle(new PaymentListQuery($query->getGroupId())),
            fn (DTO\Payment $payment) => $payment->getState()->equalsValue(State::COMPLETED),
        );
        $repayments = array_map([DTO\RepaymentCandidateFactory::class, 'create'], $payments);

        $group  = $this->groups->find($query->getGroupId());
        $object = $group->getObject();
        if ($object !== null && $object->getType()->equals(Type::CAMP())) {
            $this->setRepaymentsFromCamp(new SkautisCampId($group->getObject()->getId()), $repayments);
        }

        return $repayments;
    }

    /**
     * @param DTO\RepaymentCandidate[] $repayments
     *
     * @return DTO\RepaymentCandidate[]
     */
    private function setRepaymentsFromCamp(SkautisCampId $campId, array $repayments): array
    {
        $participantsPayments = array_filter(
            array_column($this->participants->findByCamp($campId), 'repayment', 'personId'),
            fn (float $amount) => $amount > 0,
        );

        foreach ($repayments as $repayment) {
            assert($repayment instanceof DTO\RepaymentCandidate);

            $personId = $repayment->getPersonId();

            if ($personId === null || ! array_key_exists($personId, $participantsPayments)) {
                continue;
            }

            $repayment->setAmount($participantsPayments[$personId]);
        }

        return $repayments;
    }
}
