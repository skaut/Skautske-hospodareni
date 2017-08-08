<?php

namespace Model\Skautis;

use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Skautis\Wsdl\WebServiceInterface;

class UnitRepository implements IUnitRepository
{

    /** @var WebServiceInterface */
    private $webService;


    public function __construct(WebServiceInterface $webService)
    {
        $this->webService = $webService;
    }


    public function findByParent(int $parentId): array
    {
        $units = $this->webService->call('UnitAll', [
            [
                'ID_UnitParent' => $parentId,
            ],
        ]);

        if(is_object($units)) { // API returns empty object when there are no results
            return [];
        }

        return array_map(function(\stdClass $unit) {
            return new Unit($unit->ID, $unit->SortName, $unit->DisplayName);
        }, $units);
    }


    public function find(int $id)
    {
        return $this->webService->call('UnitDetail', [
            [
                'ID' => $id,
            ],
        ]);
    }


}
