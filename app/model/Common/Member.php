<?php

declare(strict_types=1);

namespace Model\Common;

use Cake\Chronos\ChronosDate;

final class Member
{
    public function __construct(private int $id, private string $name, private ChronosDate|null $birthday = null)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBirthday(): ChronosDate|null
    {
        return $this->birthday;
    }
}
