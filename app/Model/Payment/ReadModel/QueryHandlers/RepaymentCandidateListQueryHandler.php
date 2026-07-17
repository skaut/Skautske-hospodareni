<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment as DTO;
use App\Model\Event\SkautisCampId;
use App\Model\Payment\Group\Type;
use App\Model\Payment\Payment\State;
use App\Model\Payment\ReadModel\Queries\PaymentListQuery;
use App\Model\Payment\ReadModel\Queries\RepaymentCandidateListQuery;
use App\Model\Payment\Repositories\IGroupRepository;
use LogicException;

use function array_column;
use function array_filter;
use function array_key_exists;
use function array_map;

final class RepaymentCandidateListQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IGroupRepository $groups, private IParticipantRepository $participants)
    {
    }

    /** @return DTO\RepaymentCandidate[] */
    public function __invoke(RepaymentCandidateListQuery $query): array
    {
        $payments = array_filter(
            $this->queryBus->handle(new PaymentListQuery($query->getGroupId())),
            fn (DTO\Payment $payment) => $payment->getState()->equalsValue(State::COMPLETED),
        );
        $repayments = array_map([DTO\RepaymentCandidateFactory::class, 'create'], $payments);

        $group = $this->groups->find($query->getGroupId());
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
            if (! $repayment instanceof DTO\RepaymentCandidate) {
                throw new LogicException('Assertion failed.');
            }
            $personId = $repayment->getPersonId();

            if ($personId === null || ! array_key_exists($personId, $participantsPayments)) {
                continue;
            }

            $repayment->setAmount($participantsPayments[$personId]);
        }

        return $repayments;
    }
}
