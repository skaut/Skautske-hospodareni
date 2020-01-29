<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\EventModule\Components\ExportDialog;
use App\AccountancyModule\EventModule\Factories\IExportDialogFactory;
use App\AccountancyModule\Factories\GridFactory;
use Cake\Chronos\Date;
use Model\Auth\Resources\Event as EventResource;
use Model\DTO\Event\EventListItem;
use Model\Event\Commands\CancelEvent;
use Model\Event\ReadModel\Queries\EventStates;
use Model\Event\SkautisEventId;
use Skautis\Exception;
use function array_merge;
use function get_class;
use function sprintf;

class DefaultPresenter extends BasePresenter
{
    public const DEFAULT_STATE = 'draft'; //filtrovani zobrazených položek

    /** @var IExportDialogFactory */
    private $exportDialogFactory;

    /** @var GridFactory */
    private $gridFactory;

    public function __construct(IExportDialogFactory $exportDialogFactory, GridFactory $gridFactory)
    {
        parent::__construct();
        $this->exportDialogFactory = $exportDialogFactory;
        $this->gridFactory         = $gridFactory;
    }

    protected function startup() : void
    {
        parent::startup();

        $this->redrawControl('events');
        $this->setLayout('layout.new');
    }

    public function handleCancel(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(EventResource::CANCEL, $aid)) {
            $this->flashMessage('Nemáte právo na zrušení akce.', 'danger');
            $this->redirect('this');
        }

        try {
            $this->commandBus->handle(new CancelEvent(new SkautisEventId($aid)));
            $this->flashMessage('Akce byla zrušena', 'success');
        } catch (Exception $e) {
            $this->flashMessage('Akci se nepodařilo zrušit', 'danger');
            $this->logger->error(
                sprintf('Event #%d couldn\'t be canceled. Reason: %s', $aid, $e->getMessage()),
                ['exception' => get_class($e)]
            );
        }

        $this->redirect('this');
    }

    protected function createComponentExportDialog() : ExportDialog
    {
        return $this->exportDialogFactory->create($this['grid']->getFilteredAndSortedData());
    }

    protected function createComponentGrid() : DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/../templates/Default/@eventsGrid.latte',
            ['accessCreate' => $this->authorizator->isAllowed(EventResource::CREATE, null)]
        );

        $grid->addColumnLink('name', 'Název', 'Event:', null, ['aid' => 'id'])
            ->setSortable();

        $grid->addColumnDateTime('startDate', 'Začátek akce')
            ->setSortable();

        $grid->addColumnDateTime('endDate', 'Konec akce')
            ->setSortable();

        $grid->addColumnText('prefix', 'Prefix')
            ->setSortable();

        $grid->addColumnText('state', 'Stav');

        $grid->addYearFilter('year', 'Rok')
            ->setCondition(function (EventListDataSource $dataSource, $year) : void {
                $dataSource->filterByYear($year === DataGrid::OPTION_ALL ? null : (int) ($year ?? Date::today()->year));
            });

        $states = array_merge([DataGrid::OPTION_ALL => 'Nezrušené'], $this->queryBus->handle(new EventStates()));
        $grid->addFilterSelect('state', 'Stav', $states)
            ->setCondition(function (EventListDataSource $dataSource, ?string $state) : void {
                $dataSource->filterByState($state === DataGrid::OPTION_ALL ? null : $state);
            });

        $grid->addFilterText('search', 'Název', 'name')
            ->setPlaceholder('Hledat podle názvu...');

        $grid->setDataSource(new EventListDataSource($this->queryBus));
        $grid->setDefaultSort(['startDate' => 'ASC']);

        $grid->setDefaultFilter([
            'search' => '',
            'year' => (string) Date::today()->year,
            'state' => self::DEFAULT_STATE,
        ]);

        $grid->addAction('cancel', '', ':cancel!', ['aid' => 'id'])
            ->setClass('btn btn-danger btn-sm data-confirm')
            ->setTitle('Zrušit akci')
            ->setIcon('far fa-trash-alt')
            ->setRenderCondition(function (EventListItem $event) {
                return $this->authorizator->isAllowed(EventResource::CANCEL, $event->getId());
            });

        return $grid;
    }
}
