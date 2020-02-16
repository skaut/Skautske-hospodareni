<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

class UpdateParticipant
{
    public const FIELD_DAYS       = 'days';
    public const FIELD_PAYMENT    = 'payment';
    public const FIELD_REPAYMENT  = 'repayment';
    public const FIELD_IS_ACCOUNT = 'isAccount';

    /** @var int */
    private $eventId;

    /** @var int */
    private $participantId;

    /** @var string */
    private $field;

    /** @var string */
    private $value;

    public function __construct(int $eventId, int $participantId, string $field, string $value)
    {
        $this->eventId       = $eventId;
        $this->participantId = $participantId;
        $this->field         = $field;
        $this->value         = $value;
    }

    public function getEventId() : int
    {
        return $this->eventId;
    }

    public function getParticipantId() : int
    {
        return $this->participantId;
    }

    public function getField() : string
    {
        return $this->field;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    /** @return string[] */
    public static function getCampFields() : array
    {
        return [self::FIELD_DAYS, self::FIELD_PAYMENT, self::FIELD_REPAYMENT, self::FIELD_IS_ACCOUNT];
    }

    /** @return string[] */
    public static function getEventFields() : array
    {
        return [self::FIELD_DAYS, self::FIELD_PAYMENT];
    }
}
