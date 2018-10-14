<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\Unit\UnitNotFound;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use function is_object;

final class UnitRepository implements IUnitRepository
{
    /** @var WebServiceInterface */
    private $webService;


    public function __construct(WebServiceInterface $webService)
    {
        $this->webService = $webService;
    }

    /** @return mixed[] */
    public function findByParent(int $parentId) : array
    {
        $units = $this->webService->call('UnitAll', [
            ['ID_UnitParent' => $parentId],
        ]);

        if (is_object($units)) { // API returns empty object when there are no results
            return [];
        }
        $res =[];
        foreach ($units as $u) {
            $u->ID_UnitParent = $parentId;
            $res[]            = $this->createUnit($u);
        }
        return $res;
    }

    public function find(int $id) : Unit
    {
        return $this->createUnit(
            $this->findAsStdClass($id)
        );
    }

    public function findAsStdClass(int $id) : \stdClass
    {
        try {
            return $this->webService->call('UnitDetail', [
                ['ID' => $id],
            ]);
        } catch (PermissionException $e) { // Unit doesn't exist or user has no access to it
            throw new UnitNotFound('', 0, $e);
        }
    }

    private function createUnit(\stdClass $unit) : Unit
    {
        return new Unit(
            $unit->ID,
            $unit->SortName,
            $unit->DisplayName,
            $unit->RegistrationNumber,
            $unit->ID_UnitType,
            isset($unit->ID_UnitParent) ? (int) $unit->ID_UnitParent : null
        );
    }
}
