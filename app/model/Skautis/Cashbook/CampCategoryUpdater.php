<?php

namespace Model\Skautis\Cashbook;

use Model\Cashbook\ObjectType;
use Model\Cashbook\Services\ICampCategoryUpdater;
use Model\Skautis\Mapper;
use Skautis\Skautis;

final class CampCategoryUpdater implements ICampCategoryUpdater
{
    
    /** @var Skautis */
    private $skautis;
    
    /** @var Mapper */
    private $mapper;

    public function __construct(Skautis $skautis, Mapper $mapper)
    {
        $this->skautis = $skautis;
        $this->mapper = $mapper;
    }

    public function updateCategories(int $cashbookId, array $totals): void
    {
        if(count($totals) === 0) {
            return;
        }

        $webService = $this->skautis->getWebService('user');

        $campSkautisId = $this->mapper->getSkautisId($cashbookId, ObjectType::CAMP);
        
        if($campSkautisId === NULL) {
            throw new \InvalidArgumentException("Camp #$cashbookId doesn't exist");
        }

        foreach($totals as $categoryId => $total) {
            $webService->EventCampStatementUpdate([
                'ID' => $categoryId,
                'ID_EventCamp' => $campSkautisId,
                'Ammount' => $total,
                'IsEstimate' => FALSE
            ], 'eventCampStatement');
        }
    }

}
