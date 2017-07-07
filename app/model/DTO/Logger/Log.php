<?php

namespace Model\DTO\Payment;

use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read int $unitId
 * @property-read DateTimeImmutable $date
 * @property-read int $userId
 * @property-read ?int $objectId
 * @property-read string $description
 */
class Log
{
    use SmartObject;

    /** @var int */
    private $unitId;

    /** @var DateTimeImmutable */
    private $date;

    /** @var int */
    private $userId;

    /** @var  string */
    private $description;

    /** @var int|NULL */
    private $objectId;

    public function __construct(
        int $unitId,
        DateTimeImmutable $date,
        int $userId,
        string $description,
        ?int $objectId
    )
    {
        $this->unitId = $unitId;
        $this->date = $date;
        $this->userId = $userId;
        $this->description = $description;
        $this->objectId = $objectId;
    }

}
