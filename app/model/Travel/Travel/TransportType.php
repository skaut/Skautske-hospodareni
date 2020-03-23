<?php

declare(strict_types=1);

namespace Model\Travel\Travel;

use Consistence\Enum\Enum;
use function in_array;

/**
 * @method string getValue()
 * @method static string[] getAvailableValues()
 * @method static static[] getAvailableEnums() : iterable()
 */
final class TransportType extends Enum
{
    public const CAR           = 'car';
    public const BUS           = 'bus';
    public const EXPRESS_TRAIN = 'express_train';
    public const TRAIN         = 'train';
    public const MOTORCYCLE    = 'motorcycle';
    public const ON_FOOT       = 'on_foot';
    public const AIRPLANE      = 'airplane';

    private const LABELS = [
        self::CAR => 'auto vlastní',
        self::BUS => 'autobus',
        self::EXPRESS_TRAIN => 'rychlík',
        self::TRAIN => 'osobní vlak',
        self::MOTORCYCLE => 'motocykl vlastní',
        self::ON_FOOT => 'pěšky',
        self::AIRPLANE => 'letadlo',
    ];

    public function getLabel() : string
    {
        return self::LABELS[$this->getValue()];
    }

    public function hasFuel() : bool
    {
        return in_array($this->getValue(), [self::CAR, self::MOTORCYCLE], true);
    }

    public function toString() : string
    {
        return $this->getValue();
    }

    public function __toString() : string
    {
        return $this->getValue();
    }
}
