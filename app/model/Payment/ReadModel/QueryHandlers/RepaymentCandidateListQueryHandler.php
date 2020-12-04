<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use eGen\MessageBus\QueryBus\IQueryBus;
use Model\Common\Repositories\IParticipantRepository;
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
    private IGroupRepository $groups;

    private IQueryBus $queryBus;

    private IParticipantRepository $participants;

    public function __construct(IQueryBus $queryBus, IGroupRepository $groups, IParticipantRepository $participants)
    {
        $this->queryBus     = $queryBus;
        $this->groups       = $groups;
        $this->participants = $participants;
    }

    /**
     * @return DTO\RepaymentCandidate[]
     */
    public function __invoke(RepaymentCandidateListQuery $query) : array
    {
        $payments   = array_filter(
            $this->queryBus->handle(new PaymentListQuery($query->getGroupId())),
            fn (DTO\Payment $payment) => $payment->getState()->equalsValue(State::COMPLETED),
        );
        $repayments = array_map([DTO\RepaymentCandidateFactory::class, 'create'], $payments);

        $group = $this->groups->find($query->getGroupId());
        if ($group->getObject()->getType()->equals(Type::CAMP())) {
            $this->setRepaymentsFromCamp(new SkautisCampId($group->getObject()->getId()), $repayments);
        }

        return $repayments;
    }

    /**
     * @param DTO\RepaymentCandidate[] $repayments
     *
     * @return DTO\RepaymentCandidate[]
     */
    private function setRepaymentsFromCamp(SkautisCampId $campId, array $repayments) : array
    {
        $participantsPayments = array_filter(
            array_column($this->participants->findByCamp($campId), 'repayment', 'personId'),
            fn(int $amount) => $amount > 0
        );

        foreach ($repayments as $repayment) {
            assert($repayment instanceof DTO\RepaymentCandidate);
            if (! array_key_exists($repayment->getPersonId(), $participantsPayments)) {
                continue;
            }

            $repayment->setAmount($participantsPayments[$repayment->getPersonId()]);
        }

        return $repayments;
    }
}
