<?php

declare(strict_types=1);

namespace App\Presentation\Events\Default;

use App\Components\DataGrid;
use App\Components\Event\EventListDataSource;
use App\Components\Event\ExportDialog;
use App\Components\Event\IExportDialogFactory;
use App\Components\Grids\GridFactory;
use App\Model\Auth\Resources\Event as EventResource;
use App\Model\DTO\Event\EventListItem;
use App\Model\Event\Commands\CancelEvent;
use App\Model\Event\ReadModel\Queries\EventStates;
use App\Model\Event\SkautisEventId;
use Cake\Chronos\ChronosDate;
use Skautis\Exception;

use function array_merge;
use function sprintf;

final class DefaultPresenter extends \App\BasePresenter
{
    public const DEFAULT_STATE = 'draft';

    public function __construct(private readonly IExportDialogFactory $exportDialogFactory, private readonly GridFactory $gridFactory)
    {
        parent::__construct();
    }

    public function handleCancel(int $aid): void
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
                ['exception' => $e::class],
            );
        }

        $this->redirect('this');
    }

    protected function createComponentExportDialog(): ExportDialog
    {
        return $this->exportDialogFactory->create($this['grid']->getFilteredAndSortedData());
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/@eventsGrid.latte',
            ['accessCreate' => $this->authorizator->isAllowed(EventResource::CREATE, null)],
        );

        $grid->addColumnLink('name', 'Název', ':Events:Event:', null, ['aid' => 'id'])
            ->setSortable();

        $grid->addColumnDateTime('startDate', 'Začátek akce')
            ->setSortable();

        $grid->addColumnDateTime('endDate', 'Konec akce')
            ->setSortable();

        $grid->addColumnText('prefix', 'Prefix')
            ->setSortable();

        $grid->addColumnText('state', 'Stav');

        $grid->addYearFilter('year', 'Rok')
            ->setCondition(function (EventListDataSource $dataSource, $year): void {
                $dataSource->filterByYear($year === DataGrid::OPTION_ALL ? null : (int) ($year ?? ChronosDate::today()->year));
            });

        $states = array_merge([DataGrid::OPTION_ALL => 'Nezrušené'], $this->queryBus->handle(new EventStates()));
        $grid->addFilterSelect('state', 'Stav', $states)
            ->setCondition(function (EventListDataSource $dataSource, ?string $state): void {
                $dataSource->filterByState($state === DataGrid::OPTION_ALL ? null : $state);
            });

        $grid->addFilterText('search', 'Název', 'name')
            ->setPlaceholder('Hledat podle názvu...');

        $grid->setDataSource(new EventListDataSource($this->queryBus));
        $grid->setDefaultSort(['startDate' => 'ASC']);

        $grid->setDefaultFilter([
            'search' => '',
            'year' => (string) ChronosDate::today()->year,
            'state' => self::DEFAULT_STATE,
        ]);

        $grid->addAction('detail', '', 'Event:', ['aid' => 'id'])
            ->setClass('btn btn-primary btn-sm')
            ->setTitle('Detail akce')
            ->setIcon('fi fi-rr-search');

        $grid->addAction('cancel', '', ':cancel!', ['aid' => 'id'])
            ->setClass('btn btn-danger btn-sm')
            ->setTitle('Zrušit akci')
            ->setIcon('far fa-trash-alt')
            ->addAttributes(['data-confirm' => 'Opravdu chcete zrušit tuto akci?'])
            ->setRenderCondition(function (EventListItem $event): bool {
                return $this->authorizator->isAllowed(EventResource::CANCEL, $event->getId());
            });

        return $grid;
    }
}
