<?php

declare(strict_types=1);

namespace Model\Export;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\DTO\Cashbook\Category;
use Model\Event\Event;
use Model\Event\Functions;
use Model\Event\Repositories\IEventRepository;
use Model\EventEntity;
use Model\ExportService;
use Model\ParticipantService;
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

        $queryBus->expects('handle')->withArgs(static function (CategoryListQuery $query) use ($cashbookId) : bool {
            return $query->getCashbookId()->equals($cashbookId);
        })->andReturn([
            new Category(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, 'Přijmy od účastníků', MoneyFactory::fromFloat(700.0), 'pp', Operation::INCOME(), false),
            new Category(2, 'Služby', MoneyFactory::fromFloat(50.0), 's', Operation::EXPENSE(), false),
            new Category(9, 'Převod z pokladny střediska', MoneyFactory::fromFloat(200.0), 'ps', Operation::INCOME(), true),
            new Category(7, 'Převod do stř. pokladny', MoneyFactory::fromFloat(150.0), 'pr', Operation::EXPENSE(), true),
        ]);

        // handle EventFunctions
        $queryBus->expects('handle')->andReturn(m::mock(Functions::class));

        $exportService      = new ExportService($unitService, $templateFactory, $events, $queryBus);
        $eventService       = m::mock(EventEntity::class);
        $participantService = m::mock(ParticipantService::class);
        $participantService->expects('getAll')->andReturn([]);
        $participantService->expects('getPersonsDays')->andReturn(0);

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

        $eventService->shouldReceive('getParticipants')->andReturn($participantService);
        $exportService->getEventReport($skautisEventId, $eventService);
    }
}
