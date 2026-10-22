<?php

declare(strict_types=1);

namespace App\Model\Skautis\Cashbook;

use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Camp as CampResource;
use App\Model\Cashbook\Camp;
use App\Model\Cashbook\CampBudgetUpdateNotAllowed;
use App\Model\Cashbook\CampCategory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\NegativeCampCategoryTotal;
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

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(true),
            $this->createCampRepository(),
            $this->createCampCategories([
                new CampCategory(1, Operation::INCOME(), 'Příjem od dětí', MoneyFactory::fromFloat(100.0)),
                new CampCategory(2, Operation::EXPENSE(), 'Materiál', MoneyFactory::fromFloat(50.0)),
            ]),
        );

        $updater->updateCategories(CashbookId::generate(), [1 => 200.0, 2 => 50.0]);
    }

    public function testDoesNotContactSkautisWhenCategoryTotalIsNegative(): void
    {
        $eventWebService = m::mock(WebServiceInterface::class);
        $eventWebService->shouldNotReceive('EventCampStatementUpdate');

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(true),
            $this->createCampRepository(),
            $this->createCampCategories([
                new CampCategory(1, Operation::INCOME(), 'Příjem od dětí', MoneyFactory::fromFloat(100.0)),
            ]),
        );

        $this->expectException(NegativeCampCategoryTotal::class);

        $updater->updateCategories(CashbookId::generate(), [1 => -0.01]);
    }

    public function testNegativeTotalOfCategoryThatIsNotSentToSkautisDoesNotBlockUpdate(): void
    {
        // Category 2 exists only in the cashbook, so it is never sent to Skautis and its
        // negative total must not block updating category 1.
        $eventWebService = m::mock(WebServiceInterface::class);
        $eventWebService->expects('EventCampStatementUpdate')
            ->with([
                'ID' => 1,
                'ID_EventCamp' => self::CAMP_ID,
                'Ammount' => 200.0,
                'IsEstimate' => false,
            ], 'eventCampStatement');

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(true),
            $this->createCampRepository(),
            $this->createCampCategories([
                new CampCategory(1, Operation::INCOME(), 'Příjem od dětí', MoneyFactory::fromFloat(100.0)),
            ]),
        );

        $updater->updateCategories(CashbookId::generate(), [1 => 200.0, 2 => -50.0]);
    }

    public function testResetsCategoryThatIsNoLongerInCashbookButStillHasTotalInSkautis(): void
    {
        $eventWebService = m::mock(WebServiceInterface::class);
        $eventWebService->expects('EventCampStatementUpdate')
            ->with([
                'ID' => 2,
                'ID_EventCamp' => self::CAMP_ID,
                'Ammount' => 0.0,
                'IsEstimate' => false,
            ], 'eventCampStatement');

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(true),
            $this->createCampRepository(),
            $this->createCampCategories([
                new CampCategory(1, Operation::INCOME(), 'Příjem od dětí', MoneyFactory::fromFloat(100.0)),
                new CampCategory(2, Operation::EXPENSE(), 'Materiál', MoneyFactory::fromFloat(50.0)),
            ]),
        );

        $updater->updateCategories(CashbookId::generate(), [1 => 100.0]);
    }

    public function testAllowsZeroCategoryTotal(): void
    {
        $eventWebService = m::mock(WebServiceInterface::class);
        $eventWebService->expects('EventCampStatementUpdate')
            ->with([
                'ID' => 1,
                'ID_EventCamp' => self::CAMP_ID,
                'Ammount' => 0.0,
                'IsEstimate' => false,
            ], 'eventCampStatement');

        $updater = new CampCategoryUpdater(
            $eventWebService,
            $this->createAuthorizator(true),
            $this->createCampRepository(),
            $this->createCampCategories([
                new CampCategory(1, Operation::INCOME(), 'Příjem od dětí', MoneyFactory::fromFloat(100.0)),
            ]),
        );

        $updater->updateCategories(CashbookId::generate(), [1 => 0.0]);
    }

    private function createAuthorizator(bool $isAllowed): IAuthorizator
    {
        $authorizator = m::mock(IAuthorizator::class);
        $authorizator->expects('isAllowed')
            ->with(CampResource::UPDATE_BUDGET, self::CAMP_ID)
            ->andReturn($isAllowed);

        return $authorizator;
    }

    /** @param CampCategory[] $categories */
    private function createCampCategories(array $categories): ICampCategoryRepository
    {
        $campCategories = m::mock(ICampCategoryRepository::class);
        $campCategories->expects('findForCamp')
            ->with(self::CAMP_ID)
            ->andReturn($categories);

        return $campCategories;
    }

    private function createCampRepository(): ICampRepository
    {
        $repository = m::mock(ICampRepository::class);
        $repository->expects('findByCashbookId')
            ->andReturn(new Camp(new SkautisCampId(self::CAMP_ID), CashbookId::generate()));

        return $repository;
    }
}
