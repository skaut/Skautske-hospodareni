<?php

declare(strict_types=1);

namespace Model\Export;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantStatisticsQuery;
use Model\DTO\Cashbook\CategorySummary;
use Model\DTO\Participant\Statistics;
use Model\Event\Event;
use Model\Event\Functions;
use Model\Event\Repositories\IEventRepository;
use Model\ExportService;
use Model\Services\TemplateFactory;
use Model\UnitService;
use Model\Utils\MoneyFactory;

class ExportServiceTest extends Unit
{
    public function testGetEventReport() : void
    {
        $skautisEventId  = 42;
        $unitService     = m::mock(UnitService::class);
        $templateFactory = m::mock(TemplateFactory::class);
        $events          = m::mock(IEventRepository::class);
        $events->expects('find')->andReturn(m::mock(Event::class));
        $queryBus = m::mock(QueryBus::class);

        $cashbookId = CashbookId::fromString('11bf5b37-e0b8-42e0-8dcf-dc8c4aefc000');

        // handle EventCashbookIdQuery
        $queryBus->expects('handle')
            ->withArgs(static function (EventCashbookIdQuery $q) use ($skautisEventId) : bool {
                return $q->getEventId()->toInt() === $skautisEventId;
            })->andReturn($cashbookId);

        $queryBus->expects('handle')->withArgs(static function (CategoriesSummaryQuery $query) use ($cashbookId) : bool {
            return $query->getCashbookId()->equals($cashbookId);
        })->andReturn([
            new CategorySummary(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, 'Přijmy od účastníků', MoneyFactory::fromFloat(700.0), Operation::INCOME(), false),
            new CategorySummary(2, 'Služby', MoneyFactory::fromFloat(50.0), Operation::EXPENSE(), false),
            new CategorySummary(9, 'Převod z pokladny střediska', MoneyFactory::fromFloat(200.0), Operation::INCOME(), true),
            new CategorySummary(7, 'Převod do stř. pokladny', MoneyFactory::fromFloat(150.0), Operation::EXPENSE(), true),
        ]);

        $queryBus->expects('handle')
            ->once()
            ->withArgs(function (EventParticipantStatisticsQuery $query) use ($skautisEventId) {
                return $query->getId()->toInt() === $skautisEventId;
            })
            ->andReturn(new Statistics(0, 0));

        // handle EventFunctions
        $queryBus->expects('handle')->once()->andReturn(m::mock(Functions::class));

        $exportService = new ExportService($unitService, $templateFactory, $events, $queryBus);

        $templateFactory->expects('create')->withArgs(static function (string $templatePath, array $parameters) : bool {
            if ($parameters['participantsCnt'] !== 0) {
                return false;
            }
            if ($parameters['personsDays'] !== 0) {
                return false;
            }

            $chits = [
                'virtual' => [
                    'in' => [9 => ['amount' => 200.0, 'label' => 'Převod z pokladny střediska']],
                    'out'=> [7 => ['amount' => 150.0, 'label' => 'Převod do stř. pokladny']],
                ],
                'real' => [
                    'in' => [1 => ['amount' => 700.0, 'label' => 'Přijmy od účastníků']],
                    'out'=> [
                        2 => ['amount' => 50.0, 'label' => 'Služby'],
                    ],
                ],
            ];
            if ($parameters['chits'] !== $chits) {
                return false;
            }

            if ($parameters['incomes'] !== [['amount' => 700.0, 'label' => 'Přijmy od účastníků']]) {
                return false;
            }

            if ($parameters['expenses'] !== [['amount' => 50.0, 'label' => 'Služby']]) {
                return false;
            }
            if ($parameters['totalIncome'] !== 700.0) {
                return false;
            }

            if ($parameters['totalExpense'] !== 50.0) {
                return false;
            }

            if ($parameters['virtualIncomes'] !== [['amount' => 200.0, 'label' => 'Převod z pokladny střediska']]) {
                return false;
            }

            if ($parameters['virtualExpenses'] !== [['amount' => 150.0, 'label' => 'Převod do stř. pokladny']]) {
                return false;
            }

            if ($parameters['virtualTotalIncome'] !== 200.0) {
                return false;
            }

            return $parameters['virtualTotalExpense'] === 150.0;
        });

        $exportService->getEventReport($skautisEventId);
    }
}
