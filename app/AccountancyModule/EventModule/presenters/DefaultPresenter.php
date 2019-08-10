<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\Factories\GridFactory;
use App\Forms\BaseForm;
use Doctrine\Common\Collections\ArrayCollection;
use Model\Auth\Resources\Event as EventResource;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportEvents;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Commands\CancelEvent;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Event\ReadModel\Queries\EventStates;
use Model\Event\SkautisEventId;
use Nette\Application\UI\Form;
use Nette\Http\SessionSection;
use Skautis\Exception;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\DataSource\DoctrineCollectionDataSource;
use function array_map;
use function array_merge;
use function array_reverse;
use function assert;
use function date;
use function get_class;
use function range;
use function sprintf;

class DefaultPresenter extends BasePresenter
{
    public const DEFAULT_STATE = 'draft'; //filtrovani zobrazených položek

    /** @var SessionSection */
    public $ses;

    /** @var GridFactory */
    private $gridFactory;

    public function __construct(GridFactory $gf)
    {
        parent::__construct();
        $this->gridFactory = $gf;
    }

    protected function startup() : void
    {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(self::class);
        if (! isset($this->ses->state)) {
            $this->ses->state = self::DEFAULT_STATE;
        }
        if (isset($this->ses->year)) {
            return;
        }

        $this->ses->year = date('Y');
    }

    protected function createComponentEventGrid() : DataGrid
    {
        //filtrovani zobrazených položek
        $year  = (int) ($this->ses->year ?? date('Y'));
        $state = $this->ses->state ?? null;

        $events = $this->queryBus->handle(new EventListQuery($year, $state === 'all' ? null : $state));

        $grid = $this->gridFactory->create();
        $grid->setDataSource(new DoctrineCollectionDataSource(new ArrayCollection($events), 'id'));
        $grid->addColumnText('displayName', 'Název')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnDateTime('startDate', 'Od')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnDateTime('endDate', 'Do')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnText('prefix', 'Prefix')
            ->setRenderer(function (Event $event) : ?string {
                $cashbookId = $this->queryBus->handle(
                    new EventCashbookIdQuery(new SkautisEventId($event->getId()->toInt()))
                );

                assert($cashbookId instanceof CashbookId);

                $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

                assert($cashbook instanceof Cashbook);

                return $cashbook->getChitNumberPrefix();
            });
        $grid->addColumnText('state', 'Stav');

        $grid->addAction('delete', '')
            ->setTemplate(__DIR__ . '/../templates/eventsGrid.cancel.latte');

        $grid->addGroupAction('Souhrn akcí')->onSelect[] = function (array $ids) : void {
            $this->redirect('exportEvents!', ['ids' => $ids]);
        };

        $grid->allowRowsAction(
            'delete',
            function (Event $event) : bool {
                return $this->authorizator->isAllowed(EventResource::DELETE, $event->getId()->toInt());
            }
        );

        $grid->setTemplateFile(__DIR__ . '/../templates/eventsGrid.latte');

        return $grid;
    }

    public function renderDefault() : void
    {
        $this->template->setParameters(['accessCreate' => $this->authorizator->isAllowed(EventResource::CREATE, null)]);
    }

    /**
     * @param string[] $ids
     */
    public function handleExportEvents(array $ids) : void
    {
        $ids = array_map('intval', $ids);
        $this->sendResponse(new ExcelResponse(sprintf('Souhrn-akci-%s', date('Y_n_j')), $this->queryBus->handle(new ExportEvents($ids))));
    }

    public function handleChangeYear(?int $year) : void
    {
        $this->ses->year = $year ?? 'all';
        if ($this->isAjax()) {
            $this->redrawControl('events');
        } else {
            $this->redirect('this');
        }
    }

    public function handleChangeState(?string $state = null) : void
    {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->redrawControl('events');
        } else {
            $this->redirect('this');
        }
    }

    public function handleCancel(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(EventResource::CLOSE, $aid)) {
            $this->flashMessage('Nemáte právo na zrušení akce.', 'danger');
            $this->redirect('this');
        }

        try {
            $this->commandBus->handle(new CancelEvent(new SkautisEventId($aid)));
            $this->flashMessage('Akce byla zrušena');
        } catch (Exception $e) {
            $this->flashMessage('Akci se nepodařilo zrušit', 'danger');
            $this->logger->error(
                sprintf('Event #%d couldn\'t be canceled. Reason: %s', $aid, $e->getMessage()),
                ['exception' => get_class($e)]
            );
        }

        $this->redirect('this');
    }

    protected function createComponentFormFilter() : Form
    {
        $states = array_merge(['all' => 'Nezrušené'], $this->queryBus->handle(new EventStates()));
        $years  = ['all' => 'Všechny'];
        foreach (array_reverse(range(2012, date('Y'))) as $y) {
            $years[$y] = $y;
        }
        $form = new BaseForm();
        $form->addSelect('state', 'Stav', $states)
            ->setDefaultValue($this->ses->state);

        $form->addSelect('year', 'Rok', $years)
            ->setDefaultValue($this->ses->year);

        $form->addSubmit('send', 'Hledat')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formFilterSubmitted($form);
        };

        return $form;
    }

    private function formFilterSubmitted(Form $form) : void
    {
        $v                = $form->getValues();
        $this->ses->year  = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect('default', ['aid' => $this->aid]);
    }
}
