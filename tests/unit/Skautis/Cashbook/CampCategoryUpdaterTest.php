<?php

declare(strict_types=1);

namespace App\Model\Skautis\Cashbook;

use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Camp as CampResource;
use App\Model\Cashbook\Camp;
use App\Model\Cashbook\CampBudgetUpdateNotAllowed;
use App\Model\Cashbook\CampCategory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\Repositories\ICampCategoryRepository;
use App\Model\Cashbook\Repositories\ICampRepository;
use App\Model\Event\SkautisCampId;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Mockery as m;
use Skautis\Wsdl\WebServiceInterface;

final class CampCategoryUpdaterTest extends Unit
{
    private const CAMP_ID = 19098;

    public function testDoesNotUpdateSkautisWhenCurrentUserCannotUpdateCampBudget(): void
    {
        $eventWebService = m::mock(WebServiceInterface::class);
        $eventWebService->shouldNotReceive('EventCampStatementUpdate');

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(false),
            $this->createCampRepository(),
            m::mock(ICampCategoryRepository::class),
        );

        $this->expectException(CampBudgetUpdateNotAllowed::class);

        $updater->updateCategories(CashbookId::generate(), [1 => 100.0]);
    }

    public function testUpdatesOnlyChangedCategoriesWhenCurrentUserCanUpdateCampBudget(): void
    {
        $eventWebService = m::mock(WebServiceInterface::class);
        $eventWebService->expects('EventCampStatementUpdate')
            ->with([
                'ID' => 1,
                'ID_EventCamp' => self::CAMP_ID,
                'Ammount' => 200.0,
                'IsEstimate' => false,
            ], 'eventCampStatement');

        $campCategories = m::mock(ICampCategoryRepository::class);
        $campCategories->expects('findForCamp')
            ->with(self::CAMP_ID)
            ->andReturn([
                new CampCategory(1, Operation::INCOME(), 'Příjem od dětí', MoneyFactory::fromFloat(100.0)),
                new CampCategory(2, Operation::EXPENSE(), 'Materiál', MoneyFactory::fromFloat(50.0)),
            ]);

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(true),
            $this->createCampRepository(),
            $campCategories,
        );

        $updater->updateCategories(CashbookId::generate(), [1 => 200.0, 2 => 50.0]);
    }

    private function createAuthorizator(bool $isAllowed): IAuthorizator
    {
        $authorizator = m::mock(IAuthorizator::class);
        $authorizator->expects('isAllowed')
            ->with(CampResource::UPDATE_BUDGET, self::CAMP_ID)
            ->andReturn($isAllowed);

        return $authorizator;
    }

    private function createCampRepository(): ICampRepository
    {
        $repository = m::mock(ICampRepository::class);
        $repository->expects('findByCashbookId')
            ->andReturn(new Camp(new SkautisCampId(self::CAMP_ID), CashbookId::generate()));

        return $repository;
    }
}
