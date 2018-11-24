<?php

declare(strict_types=1);

namespace Model\Participant;

use Assert\Assert;

final class Event
{
    public const GENERAL = 'General';
    public const CAMP    = 'Camp';

    /** @var int */
    private $id;

    /** @var string */
    private $type;

    public function __construct(string $type, int $id)
    {
        Assert::that($type)->inArray([self::GENERAL, self::CAMP]);

        $this->type = $type;
        $this->id   = $id;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getType() : string
    {
        return $this->type;
    }
}
