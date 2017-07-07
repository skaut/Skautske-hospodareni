<?php

namespace Model\Logger;

class Log
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var \DateTimeImmutable */
    private $date;

    /** @var int */
    private $userId;

    /** @var string */
    private $description;

    /** @var int|NULL */
    private $objectId;

    public function __construct(int $unitId, int $userId, string $desc, ?int $objectId = NULL)
    {
        $this->unitId = $unitId;
        $this->date = new \DateTimeImmutable();
        $this->userId = $userId;
        $this->description = $desc;
        $this->objectId = $objectId;
    }

}
