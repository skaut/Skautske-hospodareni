<?php

declare(strict_types=1);

namespace Extension\Doctrine\Entity;

interface SoftDeletableEntityInterface
{
    public function setDeleted(bool $deleted = true): void;

    public function isDeleted(): bool;
}
