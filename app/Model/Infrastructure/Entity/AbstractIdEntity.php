<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @implements SingleIdentifierEntityInterface<int> */
#[MappedSuperclass]
abstract class AbstractIdEntity extends AbstractEntity implements SingleIdentifierEntityInterface
{
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    #[Column(name: 'id', type: Types::INTEGER, nullable: false, options: ['unsigned' => true])]
    protected int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function hasId(): bool
    {
        return isset($this->id);
    }
}
