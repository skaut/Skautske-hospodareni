<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Repositories\ITypeRepository;
use Model\Travel\Travel\Type;
use Model\Travel\TypeNotFound;
use function sprintf;

class TypeRepository implements ITypeRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @throws TypeNotFound
     */
    public function find(string $shortcut) : Type
    {
        $type = $this->em->find(Type::class, $shortcut);

        if ($type === null) {
            throw new TypeNotFound(sprintf('Travel type \'%s\' was not found.', $shortcut));
        }

        return $type;
    }
}
