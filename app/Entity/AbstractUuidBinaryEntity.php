<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\CustomIdGenerator;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

/** @implements SingleIdentifierEntityInterface<UuidInterface> */
#[MappedSuperclass]
abstract class AbstractUuidBinaryEntity extends AbstractEntity implements SingleIdentifierEntityInterface
{
    #[Id]
    #[Column(type: UuidBinaryType::NAME, unique: true)]
    #[GeneratedValue(strategy: 'CUSTOM')]
    #[CustomIdGenerator(class: UuidV7Generator::class)]
    protected UuidInterface $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function hasId(): bool
    {
        return isset($this->id);
    }
}
