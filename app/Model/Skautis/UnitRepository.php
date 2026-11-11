<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use App\Model\Unit\UnitNotFound;
use Skaut\Skautis\Wsdl\PermissionException;
use Skaut\Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function is_array;

final class UnitRepository implements IUnitRepository
{
    public function __construct(private WebServiceInterface $webService)
    {
    }

    /** @return mixed[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByParent(int $parentId): array
    {
        $units = $this->webService->call('UnitAll', [
            ['ID_UnitParent' => $parentId],
        ]);

        if (! is_array($units)) {
            return []; // no results
        }

        $res = [];
        foreach ($units as $u) {
            $u->ID_UnitParent = $parentId;
            $res[] = $this->createUnit($u);
        }

        return $res;
    }

    public function find(int $id): Unit
    {
        return $this->createUnit(
            $this->findAsStdClass($id),
        );
    }

    private function findAsStdClass(int $id): stdClass
    {
        try {
            $unit = $this->webService->call('UnitDetail', [
                ['ID' => $id],
            ]);
        } catch (PermissionException $e) {
            // Unit doesn't exist (access to all units is public)
            throw new UnitNotFound('', 0, $e);
        }

        if (! $unit instanceof stdClass) {
            throw new UnitNotFound();
        }

        return $unit;
    }

    private function createUnit(stdClass $unit): Unit
    {
        return new Unit(
            $unit->ID,
            $unit->SortName,
            $unit->DisplayName,
            $unit->IC ?? null,
            $unit->Street,
            $unit->City,
            $unit->Postcode,
            $unit->RegistrationNumber,
            $unit->ID_UnitType,
            isset($unit->ID_UnitParent) ? (int) $unit->ID_UnitParent : null,
        );
    }
}
