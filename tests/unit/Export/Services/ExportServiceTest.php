<?php

declare(strict_types=1);

namespace App\Model\Export;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantStatisticsQuery;
use App\Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\DTO\Participant\Statistics;
use App\Model\Event\Education;
use App\Model\Event\Event;
use App\Model\Event\Functions;
use App\Model\Event\ReadModel\Queries\EducationCourseParticipationStatsQuery;
use App\Model\Event\ReadModel\Queries\EducationFunctions;
use App\Model\Event\ReadModel\Queries\EducationParticipantParticipationStatsQuery;
use App\Model\Event\ReadModel\Queries\EducationQuery;
use App\Model\Event\ReadModel\Queries\EducationTermsQuery;
use App\Model\Event\Repositories\IEventRepository;
use App\Model\Event\SkautisEducationId;
use App\Model\Invoice\InvoiceImageStorage;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Services\TemplateFactory;
use App\Model\Unit\UnitService;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use LogicException;
use Mockery as m;

class ExportServiceTest extends Unit
{
    public function testGetEventReport(): void
    {
        $skautisEventId = 42;
        $unitService = m::mock(UnitService::class);
        $templateFactory = m::mock(TemplateFactory::class);
        $events = m::mock(IEventRepository::class);
        $events->expects('find')->andReturn(m::mock(Event::class));
        $queryBus = m::mock(QueryBus::class);
        $invoiceUnitSettings = m::mock(InvoiceUnitSettingRepository::class);

        $cashbookId = CashbookId::fromString('11bf5b37-e0b8-42e0-8dcf-dc8c4aefc000');

        // handle EventCashbookIdQuery
        $queryBus->expects('handle')
            ->withArgs(static function (EventCashbookIdQuery $q) use ($skautisEventId): bool {
                return $q->getEventId()->toInt() === $skautisEventId;
            })->andReturn($cashbookId);

        $queryBus->expects('handle')->withArgs(static function (CategoriesSummaryQuery $query) use ($cashbookId): bool {
            return $query->getCashbookId()->equals($cashbookId);
        })->andReturn([
            new CategorySummary(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, 'Přijmy od účastníků', MoneyFactory::fromFloat(700.0), Operation::INCOME(), false),
            new CategorySummary(2, 'Služby', MoneyFactory::fromFloat(50.0), Operation::EXPENSE(), false),
            new CategorySummary(900, 'Vlastní příjem', MoneyFactory::fromFloat(20.0), Operation::INCOME(), false),
            new CategorySummary(901, 'Vlastní výdaj', MoneyFactory::fromFloat(30.0), Operation::EXPENSE(), false),
            new CategorySummary(902, 'Rezerva', MoneyFactory::fromFloat(10.0), Operation::EXPENSE(), false),
            new CategorySummary(9, 'Převod z pokladny střediska', MoneyFactory::fromFloat(200.0), Operation::INCOME(), true),
            new CategorySummary(7, 'Převod do stř. pokladny', MoneyFactory::fromFloat(150.0), Operation::EXPENSE(), true),
        ]);
        $queryBus->expects('handle')->withArgs(static function ($query) use ($cashbookId): bool {
            return $query instanceof FinalRealBalanceQuery && $query->getCashbookId()->equals($cashbookId);
        })->andReturn(MoneyFactory::fromFloat(630.0));

        $queryBus->expects('handle')
            ->once()
            ->withArgs(function (EventParticipantStatisticsQuery $query) use ($skautisEventId) {
                return $query->getId()->toInt() === $skautisEventId;
            })
            ->andReturn(new Statistics(0, 0));

        // handle EventFunctions
        $queryBus->expects('handle')->once()->andReturn(m::mock(Functions::class));

        $exportService = new ExportService(
            $unitService,
            $templateFactory,
            $events,
            $queryBus,
            $invoiceUnitSettings,
            new InvoiceImageStorage(new Filesystem(new InMemoryFilesystemAdapter()), '/tmp'),
        );

        $parameters = [];
        $templateFactory->expects('create')->withArgs(static function (string $templatePath, array $templateParameters) use (&$parameters): bool {
            $parameters = $templateParameters;

            return $templatePath === dirname(__DIR__, 4).'/app/Model/Export/templates/eventReport.latte';
        })->andReturn('');

        $exportService->getEventReport($skautisEventId);

        self::assertSame(0, $parameters['participantsCnt']);
        self::assertSame(0, $parameters['personsDays']);
        self::assertSame([
            ['label' => 'Od dětí a roverů', 'amount' => 700.0],
            ['label' => 'Od dospělých', 'amount' => 0.0],
            ['label' => 'Ostatní příjmy', 'amount' => 20.0],
            ['label' => 'Příspěvky samosprávy', 'amount' => 0.0],
            ['label' => 'Vlastní finanční prostředky', 'amount' => 0.0],
        ], $parameters['incomes']);
        self::assertSame([
            ['label' => 'Doprava osob a materiálu', 'amount' => 0.0],
            ['label' => 'Ostatní služby', 'amount' => 50.0],
            ['label' => 'Nájem', 'amount' => 0.0],
            ['label' => 'Potraviny, stravné', 'amount' => 0.0],
            ['label' => 'Cestovné', 'amount' => 0.0],
            ['label' => 'Materiál', 'amount' => 0.0],
            ['label' => 'Vybavení', 'amount' => 0.0],
            ['label' => 'Ostatní výdaje', 'amount' => 30.0],
            ['label' => 'Rezerva', 'amount' => 10.0],
        ], $parameters['expenses']);
        self::assertSame(720.0, $parameters['totalIncome']);
        self::assertSame(90.0, $parameters['totalExpense']);
        self::assertSame([['label' => 'Převod z pokladny jednotky', 'amount' => 200.0]], $parameters['virtualIncomes']);
        self::assertSame([['label' => 'Převod do pokladny jednotky', 'amount' => 150.0]], $parameters['virtualExpenses']);
        self::assertSame(200.0, $parameters['virtualTotalIncome']);
        self::assertSame(150.0, $parameters['virtualTotalExpense']);
        self::assertSame(630.0, $parameters['finalRealBalance']);
    }

    /**
     * Vzdělávačka bez dotace má grantId null – statistiky účasti (z dotace) se v takovém případě vůbec
     * nedotazují (jinak by padly na `toInt()` na null), personDaysReal je 0.
     */
    public function testGetEducationReportWithoutGrantDoesNotQueryParticipantStats(): void
    {
        $cashbookId = CashbookId::fromString('11bf5b37-e0b8-42e0-8dcf-dc8c4aefc000');

        $education = m::mock(Education::class);
        $education->shouldReceive('getGrantId')->andReturn(null);

        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(static function (object $query) use ($cashbookId, $education) {
            return match (true) {
                $query instanceof EducationCashbookIdQuery => $cashbookId,
                $query instanceof CategoriesSummaryQuery => [],
                $query instanceof FinalRealBalanceQuery => MoneyFactory::zero(),
                $query instanceof EducationQuery => $education,
                $query instanceof EducationTermsQuery => [],
                $query instanceof EducationCourseParticipationStatsQuery => [],
                $query instanceof EducationFunctions => m::mock(Functions::class),
                $query instanceof EducationParticipantParticipationStatsQuery => throw new LogicException('Statistiky účasti se nemají dotazovat bez dotace.'),
                default => throw new LogicException('Neočekávaný dotaz '.$query::class),
            };
        });

        $templateFactory = m::mock(TemplateFactory::class);
        $templateFactory->expects('create')->withArgs(static function (string $file, array $params): bool {
            return $params['personDaysReal'] === 0 && $params['participantsAccepted'] === 0;
        })->andReturn('');

        $exportService = new ExportService(
            m::mock(UnitService::class),
            $templateFactory,
            m::mock(IEventRepository::class),
            $queryBus,
            m::mock(InvoiceUnitSettingRepository::class),
            new InvoiceImageStorage(new Filesystem(new InMemoryFilesystemAdapter()), '/tmp'),
        );

        $exportService->getEducationReport(new SkautisEducationId(1786), 2026);
    }
}
