<?php

namespace Model\Event;

final class SkautisEventId
{

    /** @var int */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * @deprecated use self::toInt()
     */
    public function getValue(): int
    {
        return $this->value;
    }

}
