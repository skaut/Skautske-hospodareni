<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Travel\Travel\TransportType;
use Consistence\Enum\Enum;

final class TravelTransportTypeType extends AbstractEnumType
{
    public const NAME = 'travel_transport_type';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return TransportType::class;
    }
}
