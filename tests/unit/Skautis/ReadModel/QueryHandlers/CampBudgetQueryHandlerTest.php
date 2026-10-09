<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\QueryHandlers;

use App\Model\DTO\Skautis\BudgetEntry;
use App\Model\Event\SkautisCampId;
use App\Model\Skautis\ReadModel\Queries\CampBudgetQuery;
use Codeception\Test\Unit;
use Mockery as m;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function array_filter;
use function array_map;
use function array_values;

final class CampBudgetQueryHandlerTest extends Unit
{
    private const CAMP_ID = 20341;

    public function testSortsStandardCategoriesAndKeepsCustomEntriesAfterThem(): void
    {
        $webService = m::mock(WebServiceInterface::class);
        $webService->expects('EventCampStatementAll')
            ->with([
                'ID_EventCamp' => self::CAMP_ID,
                'IsEstimate' => true,
            ])
            ->andReturn($this->entries([
                ['Vlastní náklad', 11.0, false],
                ['Příjem od dospělých', 20.0, true],
                ['Rezerva', 30.0, false],
                ['Příjem od dětí', 40.0, true],
                ['Materiál', 50.0, false],
                ['Vlastní příjem', 60.0, true],
                ['Doprava osob a materiálu', 70.0, false],
                ['Ostatní příjmy', 80.0, true],
            ]));

        $entries = (new CampBudgetQueryHandler($webService))(new CampBudgetQuery(new SkautisCampId(self::CAMP_ID)));

        self::assertSame(
            ['Příjem od dětí', 'Příjem od dospělých', 'Ostatní příjmy', 'Vlastní příjem'],
            $this->names($entries, true),
        );
        self::assertSame(
            ['Doprava osob a materiálu', 'Materiál', 'Rezerva', 'Vlastní náklad'],
            $this->names($entries, false),
        );
    }

    /**
     * @param  array<array{string, float, bool}> $entries
     * @return stdClass[]
     */
    private function entries(array $entries): array
    {
        return array_map(static fn (array $entry): stdClass => (object) [
            'EventCampStatementType' => $entry[0],
            'Ammount' => $entry[1],
            'IsRevenue' => $entry[2],
        ], $entries);
    }

    /**
     * @param  BudgetEntry[] $entries
     * @return string[]
     */
    private function names(array $entries, bool $income): array
    {
        return array_values(array_map(
            static fn (BudgetEntry $entry): string => $entry->getName(),
            array_filter($entries, static fn (BudgetEntry $entry): bool => $entry->isIncome() === $income),
        ));
    }
}
