<?php

declare(strict_types=1);

namespace Model\Travel\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\Travel\ReadModel\Queries\TransportTypeQuery;
use Model\Travel\Travel\Type;
use Model\Travel\TypeNotFound;
use function sprintf;

final class TransportTypeHandler
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws TypeNotFound
     */
    public function __invoke(TransportTypeQuery $query) : Type
    {
        /** @var Type|null $res */
        $res = $this->entityManager->getRepository(Type::class)->find($query->getShortcut());
        if ($res === null) {
            throw new TypeNotFound(sprintf('Travel type \'%s\' was not found.', $query->getShortcut()));
        }
        return $res;
    }
}
