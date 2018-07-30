<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook\Repositories;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;
use function array_map;
use function array_slice;
use function count;
use function ksort;

/**
 * @see https://is.skaut.cz/JunakWebservice/Events.asmx?WSDL
 */
final class CampCategoryRepositoryTest extends Unit
{
    private const CAMP_ID = 666;

    private const CATEGORIES = [
        [
            'ID' => 4,
            'IsRevenue' => false,
            'EventCampStatementType' => 'Rezerva',
            'Ammount' => '2000.0',
            'ID_EventCampStatementType' => ICategory::CAMP_RESERVE_ID,
        ],
        [
            'ID' => 1,
            'IsRevenue' => true,
            'EventCampStatementType' => 'Příjem od dětí',
            'Ammount' => '2400.15',
            'ID_EventCampStatementType' => null,
        ],
        [
            'ID' => 3,
            'IsRevenue' => true,
            'EventCampStatementType' => 'Příjem od dospělých',
            'Ammount' => '1000',
            'ID_EventCampStatementType' => null,
        ],
        [
            'ID' => 5,
            'IsRevenue' => false,
            'EventCampStatementType' => 'Materiál',
            'Ammount' => '0',
            'ID_EventCampStatementType' => null,
        ],
    ];

    public function test() : void
    {
        $webserviceResult = array_map(function (array $category) : \stdClass {
            return (object) $category;
        }, self::CATEGORIES);

        $repository = $this->prepareRepository($webserviceResult);

        $categories = $repository->findForCamp(self::CAMP_ID);

        $this->assertCount(
            count(self::CATEGORIES) - 1, // Repository should not contain reserve
            $categories
        );

        $expectedCategories = array_slice(array_map('array_values', self::CATEGORIES), 1);

        foreach ($expectedCategories as $index => [$id, $isIncome, $name, $amount]) {
            $category = $categories[$index];

            $this->assertSame($id, $category->getId());
            $this->assertSame($category->getOperationType()->getValue(), $isIncome ? Operation::INCOME : Operation::EXPENSE);
            $this->assertSame($name, $category->getName());
            $this->assertTrue($category->getTotal()->equals(MoneyFactory::fromFloat((float) $amount)));
        }
    }

    /**
     * IMHO this is seems very improbable, but WSDL states, that no response may be returned.
     * Skautis client returns empty stdClass in that case.
     */
    public function testReturnEmptyResponse() : void
    {
        $repository = $this->prepareRepository(new \stdClass());

        $this->assertEmpty(
            $repository->findForCamp(self::CAMP_ID)
        );
    }

    /**
     * @param \stdClass|array $webserviceResult
     */
    private function prepareRepository($webserviceResult) : CampCategoryRepository
    {
        $service = m::mock(WebServiceInterface::class);

        $service->expects('EventCampStatementAll')
            ->withArgs(function (array $parameters) : bool {
                $expectedParameters = [
                    'ID_EventCamp' => self::CAMP_ID,
                    'IsEstimate' => false,
                ];

                ksort($expectedParameters);
                ksort($parameters);

                return $expectedParameters === $parameters;
            })->andReturn($webserviceResult);

        return new CampCategoryRepository($service);
    }
}
