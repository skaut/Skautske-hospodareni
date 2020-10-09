<?php

declare(strict_types=1);

namespace Model\Common;

use Cake\Chronos\Date;

final class Member
{
    private int $id;

    private string $name;

    /** @var Date|null */
    private $birthday;

    public function __construct(int $id, string $name, ?Date $birthday)
    {
        $this->id       = $id;
        $this->name     = $name;
        $this->birthday = $birthday;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getBirthday() : ?Date
    {
        return $this->birthday;
    }
}
