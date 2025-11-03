<?php

declare(strict_types=1);

namespace Entity;

/** @template T */
interface SingleIdentifierEntityInterface
{
    /** @return T */
    public function getId(): mixed;

    public function hasId(): bool;
}
