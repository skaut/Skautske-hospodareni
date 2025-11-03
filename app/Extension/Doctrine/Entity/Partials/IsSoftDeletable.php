<?php

declare(strict_types=1);

namespace Extension\Doctrine\Entity\Partials;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;

trait IsSoftDeletable
{
    #[Column(type: Types::BOOLEAN)]
    protected bool $deleted = false;

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted = true): void
    {
        $this->deleted = $deleted;
    }
}
