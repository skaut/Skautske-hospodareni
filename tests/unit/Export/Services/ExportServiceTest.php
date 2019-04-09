<?php

declare(strict_types=1);

namespace Model\Export;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\Repositories\IStaticCategoryRepository;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitItem;
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
        $unitService     = $service = m::mock(UnitService::class);
        $categories      = m::mock(IStaticCategoryRepository::class);
        $templateFactory = m::mock(TemplateFactory::class);
        $events          = m::mock(IEventRepository::class);
        $events->expects('find')->andReturn(m::mock(Event::class));
        $queryBus = m::mock(QueryBus::class);

        $types = [m::mock(Category\ObjectType::class)];

        $catIncome         = new Category(1, 'Přijmy od účastníků', 'pp', Operation::get(Operation::INCOME), $types, false, 100);
        $catIncome2        = new Category(11, 'Hromadný příjem od úč.', 'hpd', Operation::get(Operation::INCOME), $types, false, 100);
        $catExpense        = new Category(2, 'Služby', 's', Operation::get(Operation::EXPENSE), $types, false, 100);
        $catIncomeVirtual  = new Category(9, 'Převod z pokladny střediska', 'ps', Operation::get(Operation::INCOME), $types, true, 100);
        $catExpenseVirtual = new Category(7, 'Převod do stř. pokladny', 'pr', Operation::get(Operation::EXPENSE), $types, true, 100);

        $categoriesArray = [
            $catIncome,
            $catIncome2,
            $catExpense,
            $catIncomeVirtual,
            $catExpenseVirtual,
        ];

        $categories->expects('findByObjectType')
            ->withArgs(function (ObjectType $type) : bool {
                return $type === ObjectType::get(ObjectType::EVENT);
            })->andReturn($categoriesArray);

        // handle EventCashbookIdQuery
        $queryBus->expects('handle')
            ->withArgs(function (EventCashbookIdQuery $q) use ($skautisEventId) : bool {
                return $q->getEventId()->toInt() === $skautisEventId;
            })->andReturn(CashbookId::fromString('11bf5b37-e0b8-42e0-8dcf-dc8c4aefc000'));

        // handle ChitListQuery
        $cashbook = m::mock(Cashbook::class);
        $queryBus->expects('handle')->andReturn([
            $this->chitGenerator(0, '300', $catIncome, Cashbook\PaymentMethod::CASH()),
            $this->chitGenerator(1, '150', $catIncome, Cashbook\PaymentMethod::CASH()),
            $this->chitGenerator(2, '250', $catIncome2, Cashbook\PaymentMethod::BANK()),
            $this->chitGenerator(3, '32', $catExpense, Cashbook\PaymentMethod::CASH()),
            $this->chitGenerator(4, '18', $catExpense, Cashbook\PaymentMethod::BANK()),
            $this->chitGenerator(5, '170', $catIncomeVirtual, Cashbook\PaymentMethod::CASH()),
            $this->chitGenerator(6, '30', $catIncomeVirtual, Cashbook\PaymentMethod::BANK()),
            $this->chitGenerator(7, '130', $catExpenseVirtual, Cashbook\PaymentMethod::CASH()),
            $this->chitGenerator(8, '20', $catExpenseVirtual, Cashbook\PaymentMethod::BANK()),
        ]);

        // handle EventFunctions
        $queryBus->expects('handle')->andReturn(m::mock(Functions::class));

        $exportService      = new ExportService($unitService, $categories, $templateFactory, $events, $queryBus);
        $eventService       = m::mock(EventEntity::class);
        $participantService = m::mock(ParticipantService::class);
        $participantService->expects('getAll')->andReturn([]);
        $participantService->expects('getPersonsDays')->andReturn(0);

        $templateFactory->expects('create')->withArgs(function (string $templatePath, array $parameters) : bool {
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
            if ($parameters['virtualTotalExpense'] !== 150.0) {
                return false;
            }

            return true;
        });

        $eventService->shouldReceive('getParticipants')->andReturn($participantService);
        $exportService->getEventReport($skautisEventId, $eventService);
    }

    private function chitGenerator(int $id, string $amount, Category $category, Cashbook\PaymentMethod $paymentMethod) : Chit
    {
        $categoryDTO = new \Model\DTO\Cashbook\Category(
            $category->getId(),
            $category->getName(),
            MoneyFactory::fromFloat(0),
            $category->getShortcut(),
            $category->getOperationType(),
            $category->isVirtual()
        );

        return new Chit(
            $id,
            new Cashbook\ChitBody(null, new Date('2018-09-22'), null, ''),
            false,
            [],
            $paymentMethod,
            [new ChitItem($id, new Cashbook\Amount($amount), $categoryDTO)],
            $category->getOperationType(),
            new Cashbook\Amount($amount)
        );
    }
}
