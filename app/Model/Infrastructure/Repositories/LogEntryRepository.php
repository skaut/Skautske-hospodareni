<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Logger;

use App\Model\Logger\Log\Type;
use App\Model\Logger\LogEntry;
use App\Model\Logger\Repositories\ILogEntryRepository;
use Doctrine\ORM\EntityManager;

final class LogEntryRepository implements ILogEntryRepository
{
    public function __construct(private EntityManager $em)
    {
    }

    /** @return LogEntry[] */
    public function findAllByTypeId(Type $type, int $typeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(LogEntry::class, 'l')
            ->where('l.typeId = :typeId')
            ->andWhere('l.type = :type')
            ->orderBy('l.date', 'DESC')
            ->setParameter('typeId', $typeId)
            ->setParameter('type', $type)
            ->getQuery()->getResult();
    }

    public function save(LogEntry $log): void
    {
        $this->em->persist($log);
        $this->em->flush();
    }
}
