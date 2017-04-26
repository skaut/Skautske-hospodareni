<?php

namespace Model\Payment\Group;

final class SkautisEntity
{

    /** @var int */
    private $id;

    /** @var Type */
    private $type;

    public function __construct(int $id, Type $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): Type
    {
        return $this->type;
    }

}
