<?php

namespace Model\Logger\Repositories;


use Doctrine\ORM\EntityManager;
use Model\Logger\Log;
use Model\Logger\Log\Type;

class LoggerRepository implements ILoggerRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return Log[]
     */
    public function findAllByTypeId(Type $type, int $typeId): array
    {


        $result = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Log::class, 'l')
            ->where("l.typeId = :typeId")
            ->andWhere("l.type = :type")
            ->orderBy('l.date', 'DESC')
            ->setParameter('typeId', $typeId)
            ->setParameter('type', $type)
            ->getQuery()->getResult();
        return $result;
    }

    public function save(Log $log): void
    {
        $this->em->persist($log);
        $this->em->flush();
    }


}
