<?php

namespace Model\Event;

final class SkautisCampId
{

    /** @var int */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

}
