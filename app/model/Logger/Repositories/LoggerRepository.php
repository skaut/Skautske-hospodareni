<?php

namespace Model\Logger\Repositories;


use Doctrine\ORM\EntityManager;
use Model\Logger\Log;

class LoggerRepository implements ILoggerRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $objectId
     * @return Log[]
     */
    public function findAllByObjectId(int $objectId): array
    {
        $result = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Log::class, 'l')
            ->where("l.objectId = :objectId")
            ->orderBy('l.date', 'DESC')
            ->setParameter('objectId', $objectId)
            ->getQuery()->getResult();
        return $result;
    }

    /**
     * @param Log $log
     */
    public function save(Log $log): void
    {
        $this->em->persist($log)->flush();
    }


}
