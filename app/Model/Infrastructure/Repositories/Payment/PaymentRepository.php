<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Payment;

use App\Model\Infrastructure\Repositories\AggregateRepository;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\Summary;
use App\Model\Payment\VariableSymbol;
use Assert\Assert;
use DateTimeImmutable;

use function array_fill_keys;

final class PaymentRepository extends AggregateRepository implements IPaymentRepository
{
    private const STATE_ORDER = [
        State::PREPARING,
        State::COMPLETED,
        State::CANCELED,
    ];

    public function find(int $id): Payment
    {
        $payment = $this->getEntityManager()->find(Payment::class, $id);

        if (! $payment instanceof Payment) {
            throw new PaymentNotFound();
        }

        return $payment;
    }

    public function summarizeByGroup(array $groupIds): array
    {
        $states = [State::PREPARING, State::COMPLETED];

        $res = $this->getEntityManager()->createQueryBuilder()
            ->select('p.groupId as groupId, p.state as state, SUM(p.amount) as amount, COUNT(p.id) as number')
            ->from(Payment::class, 'p')
            ->where('p.groupId IN (:ids)')
            ->groupBy('groupId, state')
            ->having('state IN (:states)')
            ->setParameter('ids', $groupIds)
            ->setParameter('states', $states)
            ->getQuery()
            ->getResult();

        $amounts = array_fill_keys($groupIds, array_fill_keys($states, 0));
        $counts = array_fill_keys($groupIds, array_fill_keys($states, 0));

        foreach ($res as $row) {
            $id = (int) $row['groupId'];
            $amounts[$id][$row['state']] += (float) $row['amount'];
            $counts[$id][$row['state']] = (int) $row['number'];
        }

        $summaries = array_fill_keys($groupIds, []);

        foreach ($groupIds as $id) {
            foreach ($states as $state) {
                $summaries[$id][$state] = new Summary($counts[$id][$state], $amounts[$id][$state]);
            }
        }

        return $summaries;
    }

    public function findByGroup(int $groupId): array
    {
        return $this->findByMultipleGroups([$groupId]);
    }

    public function findByMultipleGroups(array $groupIds): array
    {
        Assert::thatAll($groupIds)->integer();

        if (empty($groupIds)) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('p, e')
            ->from(Payment::class, 'p')
            ->leftJoin('p.sentEmails', 'e')
            ->where('p.groupId IN (:groupIds)')
            ->orderBy('FIELD (p.state, :states)')
            ->addOrderBy('p.id')
            ->setParameter('groupIds', $groupIds)
            ->setParameter('states', self::STATE_ORDER)
            ->getQuery()
            ->getResult();
    }

    public function findByReminder(array $groupIds): array
    {
        Assert::thatAll($groupIds)->integer();

        if (empty($groupIds)) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('p, e')
            ->from(Payment::class, 'p')
            ->leftJoin('p.sentEmails', 'e')
            ->where('p.groupId IN (:groupIds)')
            ->andWhere('p.state = :state')
            ->andWhere('p.dueDate <= :dueDate')
            ->andWhere(
                'NOT EXISTS (
            SELECT 1 FROM '.Payment\SentEmail::class.' se
            WHERE se.payment = p AND se.type = :reminderType)',
            )
            ->setParameter('groupIds', $groupIds)
            ->setParameter('state', State::PREPARING)
            ->setParameter('dueDate', (new DateTimeImmutable())->format('Y-m-d'))
            ->setParameter('reminderType', EmailType::PAYMENT_REMINDER)

            ->getQuery()->getResult();
    }

    public function save(Payment $payment): void
    {
        $this->saveAndDispatchEvents($payment);
    }

    public function saveMany(array $payments): void
    {
        if (empty($payments)) {
            return;
        }

        Assert::thatAll($payments)->isInstanceOf(Payment::class);

        foreach ($payments as $payment) {
            $this->saveAndDispatchEvents($payment);
        }
    }

    public function remove(Payment $payment): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->remove($payment);
        $entityManager->flush();
    }

    public function getMaxVariableSymbol(int $groupId): ?VariableSymbol
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(p.variableSymbol) as vs')
            ->from(Payment::class, 'p')
            ->where('p.groupId = :groupId')
            ->andWhere('p.state != :state')
            ->setParameter('groupId', $groupId)
            ->setParameter('state', State::CANCELED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null
            ? new VariableSymbol($result)
            : null;
    }

    public function existsOpenPaymentWithVariableSymbolForBankAccount(
        int $bankAccountId,
        VariableSymbol $variableSymbol,
        ?int $excludePaymentId = null,
    ): bool {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Payment::class, 'p')
            ->innerJoin(Group::class, 'g', 'WITH', 'g.id = p.groupId')
            ->where('g.bankAccount.id = :bankAccountId')
            ->andWhere('p.variableSymbol = :variableSymbol')
            ->andWhere('p.state = :state')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('variableSymbol', $variableSymbol)
            ->setParameter('state', State::PREPARING);

        if ($excludePaymentId !== null) {
            $qb->andWhere('p.id != :excludePaymentId')
                ->setParameter('excludePaymentId', $excludePaymentId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findOpenByBankAccount(int $bankAccountId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->innerJoin(Group::class, 'g', 'WITH', 'g.id = p.groupId')
            ->where('g.bankAccount.id = :bankAccountId')
            ->andWhere('p.state = :state')
            ->andWhere('p.variableSymbol IS NOT NULL')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('state', State::PREPARING)
            ->getQuery()
            ->getResult();
    }

    public function existsPairedPaymentForBankAccount(int $bankAccountId): bool
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Payment::class, 'p')
            ->innerJoin(Group::class, 'g', 'WITH', 'g.id = p.groupId')
            ->where('g.bankAccount.id = :bankAccountId')
            ->andWhere('p.transaction.id IS NOT NULL')
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
