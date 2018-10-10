<?php

declare(strict_types=1);

namespace Model\Common;

trait IntegerIdTrait
{
    /** @var int */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function toInt() : int
    {
        return $this->id;
    }
}
