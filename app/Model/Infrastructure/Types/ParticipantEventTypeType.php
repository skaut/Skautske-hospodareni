<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Participant\Payment\EventType;
use Consistence\Enum\Enum;

final class ParticipantEventTypeType extends AbstractEnumType
{
    public const NAME = 'participant_event_type';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return EventType::class;
    }
}
