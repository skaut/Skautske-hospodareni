<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Repositories\ITravelRepository;
use Model\Travel\Travel\Type;

class TravelRepository implements ITravelRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getType(string $type) : ?Type
    {
        return $this->em->getRepository(Type::class)->find($type);
    }

    /**
     * @return Type[]
     */
    public function getTypes() : array
    {
        return $this->em->getRepository(Type::class)->findBy([], ['order' => 'DESC']);
    }
}
