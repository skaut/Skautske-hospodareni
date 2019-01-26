<?php

declare(strict_types=1);

namespace Model\Travel\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\Travel\ReadModel\Queries\TransportTypesQuery;
use Model\Travel\Travel\Type;

final class TransportTypesHandler
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return Type[]
     */
    public function __invoke(TransportTypesQuery $query) : array
    {
        return $this->entityManager->getRepository(Type::class)->findBy([], ['order' => 'DESC']);
    }
}
