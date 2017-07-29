<?php

namespace Model\Skautis;

use Model\Unit\Repositories\IUnitRepository;
use Skautis\Wsdl\WebServiceInterface;

class UnitRepository implements IUnitRepository
{

    /** @var WebServiceInterface */
    private $webService;


    public function __construct(WebServiceInterface $webService)
    {
        $this->webService = $webService;
    }


    public function findByParent(int $parentId)
    {
        return $this->webService->call('UnitAll', [
            [
                'ID_UnitParent' => $parentId,
            ],
        ]);
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
