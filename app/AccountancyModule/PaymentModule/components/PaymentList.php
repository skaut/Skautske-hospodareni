<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\Factories\GridFactory;
use App\AccountancyModule\Grids\DtoListDataSource;
use Model\Common\Services\QueryBus;
use Model\DTO\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\ReadModel\Queries\PaymentListQuery;

use function array_flip;
use function array_reverse;
use function strcoll;
use function usort;

final class PaymentList extends BaseControl
{
    private const STATE_ORDER = [
        State::PREPARING,
        State::COMPLETED,
        State::CANCELED,
    ];

    public function __construct(private int $groupId, private bool $isEditable, private QueryBus $queryBus, private GridFactory $gridFactory)
    {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/PaymentList.latte');
        $this->template->render();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/templates/PaymentList.grid.latte',
            ['isEditable' => $this->isEditable],
        );
        $grid->setRememberState(false, true);
        $grid->setColumnsHideable();

        $grid->addColumnText('name', 'Název/účel')
            ->setSortable()
            ->setSortableCallback(function (DtoListDataSource $dataSource, array $sort): DtoListDataSource {
                $data = $dataSource->getData();

                usort($data, fn (Payment $a, Payment $b) => strcoll($a->getName(), $b->getName()));

                return new DtoListDataSource($sort['name'] === DataGrid::SORT_ASC ? $data : array_reverse($data));
            })
            ->getElementPrototype('td')
            ->setAttribute('class', 'w-18');

        $grid->addColumnText('recipientsString', 'E-mail')
            ->addCellAttributes(['class' => 'small'])
            ->setSortable();

        $grid->addColumnText('amount', 'Částka')
            ->setSortable();

        $grid->addColumnText('variableSymbol', 'VS')
            ->setSortable();

        $grid->addColumnText('constantSymbol', 'KS')
            ->setSortable()
            ->setDefaultHide();

        $grid->addColumnDateTime('dueDate', 'Splatnost')
            ->setSortable();

        $grid->addColumnDateTime('Note', 'Poznámka')
            ->setSortable()
            ->setDefaultHide();

        $grid->addColumnText('state', 'Stav')
            ->setSortable()
            ->setSortableCallback(function (DtoListDataSource $dataSource, array $sort): DtoListDataSource {
                $statePriority = array_flip(self::STATE_ORDER);
                $data          = $dataSource->getData();

                usort($data, function (Payment $a, Payment $b) use ($statePriority): int {
                    return $statePriority[$a->getState()->toString()] <=> $statePriority[$b->getState()->toString()];
                });

                return new DtoListDataSource($sort['state'] === DataGrid::SORT_ASC ? $data : array_reverse($data));
            });

        $grid->addColumnText('actions', 'Akce');

        $grid->setDataSource(new DtoListDataSource($this->queryBus->handle(new PaymentListQuery($this->groupId))));

        $grid->setDefaultSort(['state' => DataGrid::SORT_ASC]);

        return $grid;
    }
}
