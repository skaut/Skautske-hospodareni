<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Repositories\ITravelRepository;
use Model\Travel\Travel\Type;
use Model\Travel\TypeNotFound;
use function sprintf;

class TravelRepository implements ITravelRepository
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @throws TypeNotFound
     */
    public function getType(string $type) : Type
    {
        /** @var Type|null $res */
        $res = $this->entityManager->getRepository(Type::class)->find($type);
        if ($res === null) {
            throw new TypeNotFound(sprintf('Travel type \'%s\' was not found.', $type));
        }
        return $res;
    }

    /**
     * @return Type[]
     */
    public function findAll() : array
    {
        return $this->entityManager->getRepository(Type::class)->findBy([], ['order' => 'DESC']);
    }
}
