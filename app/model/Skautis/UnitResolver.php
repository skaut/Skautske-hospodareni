<?php

namespace Model\Skautis;

use Model\Payment\IUnitResolver;
use Model\UnitService;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;

final class UnitResolver implements IUnitResolver
{

    /** @var CacheDecorator */
    private $units;

    public function __construct(Skautis $skautis)
    {
        $this->units = new CacheDecorator($skautis->getWebService('org'), new ArrayCache());
    }


    public function getOfficialUnitId(int $unitId): int
    {
        $unit = $this->getDetail($unitId);

        if (!in_array($unit->ID_UnitType, UnitService::OFFICIAL_UNIT_TYPES)) {
            $parent = $unit->ID_UnitParent;
            $unit = $this->getOfficialUnitId($parent);
        }

        return $unit->ID;
    }


    public function getDetail(int $unitId): \stdClass
    {
        return $this->units->call('UnitDetail', [ ["ID" => $unitId] ]);
    }

}
