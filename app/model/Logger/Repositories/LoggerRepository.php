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


    //    public function findAllByUnit(int $unitId): array
    //    {
    //
    //    }

    public function save(Log $log): void
    {
        $this->em->persist($log)->flush();
    }


}
