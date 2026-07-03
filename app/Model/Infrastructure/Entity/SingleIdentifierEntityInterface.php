<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Entity;

/** @template T */
interface SingleIdentifierEntityInterface
{
    /** @return T */
    public function getId(): mixed;

    public function hasId(): bool;
}
