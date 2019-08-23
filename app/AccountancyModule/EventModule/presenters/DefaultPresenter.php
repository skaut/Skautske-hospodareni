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
use function array_map;
use function array_merge;
use function array_reverse;
use function date;
use function get_class;
use function range;
use function Safe\array_combine;
use function sprintf;

class DefaultPresenter extends BasePresenter
{
    public const DEFAULT_STATE = 'draft'; //filtrovani zobrazených položek

    private const YEAR_ALL  = 'all';
    private const STATE_ALL = 'all';

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
        if (! $this->authorizator->isAllowed(EventResource::DELETE, $aid)) {
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

        $grid->addColumnLink('name', 'Název', 'Event:')
            ->setSortable();

        $grid->addColumnDateTime('startDate', 'Začátek akce')
            ->setSortable();

        $grid->addColumnDateTime('endDate', 'Konec akce')
            ->setSortable();

        $grid->addColumnText('prefix', 'Prefix')
            ->setSortable();

        $grid->addColumnText('state', 'Stav');

        $grid->addFilterSelect('year', 'Rok', $this->getYearOptions(), 'year')
            ->setCondition(function (EventListDataSource $dataSource, $year) : void {
                $dataSource->filterByYear($year === self::YEAR_ALL ? null : (int) ($year ?? Date::today()->year));
            });

        $states = array_merge([self::STATE_ALL => 'Nezrušené'], $this->queryBus->handle(new EventStates()));
        $grid->addFilterSelect('state', 'Stav', $states)
            ->setCondition(function (EventListDataSource $dataSource, ?string $state) : void {
                $dataSource->filterByState($state ?? self::DEFAULT_STATE);
            });

        $grid->addFilterText('search', 'Název', 'name')
            ->setPlaceholder('Hledat podle názvu...');

        $grid->setDataSource(new EventListDataSource($this->queryBus));

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
                return $this->authorizator->isAllowed(EventResource::DELETE, $event->getId());
            });

        return $grid;
    }

    /**
     * @return array<string, string>
     */
    private function getYearOptions() : array
    {
        $years = array_map(
            function (int $year) : string {
                return (string) $year;
            },
            array_reverse(range(2012, (int) date('Y')))
        );

        return [self::YEAR_ALL => 'Všechny'] + array_combine($years, $years);
    }
}
