<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Factories\GridFactory;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Model\Auth\Resources\Event;
use Model\Event\Commands\CancelEvent;
use Model\Event\Commands\Event\CreateEvent;
use Model\Event\ReadModel\Queries\EventScopes;
use Model\Event\ReadModel\Queries\EventStates;
use Model\Event\ReadModel\Queries\EventTypes;
use Model\Event\ReadModel\Queries\NewestEventId;
use Model\Event\SkautisEventId;
use Model\ExcelService;
use MyValidators;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Skautis\Exception;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reverse;
use function date;
use function get_class;
use function range;
use function sprintf;

class DefaultPresenter extends BasePresenter
{
    public const DEFAULT_STATE = 'draft'; //filtrovani zobrazených položek

    public $ses;

    /** @var ExcelService */
    private $excelService;

    /** @var GridFactory */
    private $gridFactory;

    public function __construct(ExcelService $excel, GridFactory $gf)
    {
        parent::__construct();
        $this->excelService = $excel;
        $this->gridFactory  = $gf;
    }

    protected function startup() : void
    {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (! isset($this->ses->state)) {
            $this->ses->state = self::DEFAULT_STATE;
        }
        if (isset($this->ses->year)) {
            return;
        }

        $this->ses->year = date('Y');
    }

    protected function createComponentEventGrid()
    {
        //filtrovani zobrazených položek
        $year  = $this->ses->year ?? date('Y');
        $state = $this->ses->state ?? null;
        $list  = $this->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $list[$key]['accessDelete'] = $this->authorizator->isAllowed(Event::DELETE, (int) $value['ID']);
            $list[$key]['accessDetail'] = $this->authorizator->isAllowed(Event::ACCESS_DETAIL, (int) $value['ID']);
        }

        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('ID');
        $grid->setDataSource($list);
        $grid->addColumnLink('DisplayName', 'Název', 'Event:default', null, ['aid' => 'ID'])->setSortable()->setFilterText();
        $grid->addColumnDateTime('StartDate', 'Od')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnDateTime('EndDate', 'Do')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnText('prefix', 'Prefix')->setSortable();
        $grid->addColumnText('state', 'Stav');

        $grid->addAction('delete', '')
            ->setTemplate(__DIR__ . '/../templates/eventsGrid.cancel.latte');

        $grid->addGroupAction('Souhrn akcí')->onSelect[] = function (array $ids) : void {
            $this->redirect('exportEvents!', ['ids' => $ids]);
        };

        $grid->allowRowsAction(
            'delete',
            function ($item) {
                if (! array_key_exists('accessDelete', $item)) {
                    return true;
                }
                return $item['accessDelete'];
            }
        );

        $grid->setTemplateFile(__DIR__ . '/../templates/eventsGrid.latte');
        return $grid;
    }

    public function renderDefault() : void
    {
        $this['formFilter']['state']->setDefaultValue($this->ses->state);
        $this['formFilter']['year']->setDefaultValue($this->ses->year);
        $this->template->accessCreate = $this->authorizator->isAllowed(Event::CREATE, null);
    }

    public function handleExportEvents(array $ids) : void
    {
        $ids = array_map('intval', $ids);
        $this->excelService->getEventSummaries($ids, $this->eventService);
        $this->terminate();
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

    public function handleChangeState(?string $state) : void
    {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->redrawControl('events');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * zruší akci
     */
    public function handleCancel(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Event::CLOSE, $aid)) {
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
        $form->addSelect('state', 'Stav', $states);
        $form->addSelect('year', 'Rok', $years);
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

    public function isDateValidator($item, $args)
    {
        return $item === null ? false : true;
    }

    /**
     * @throws BadRequestException
     */
    protected function createComponentFormCreate() : Form
    {
        $scopes = $this->queryBus->handle(new EventScopes());
        $types  = $this->queryBus->handle(new EventTypes());
        $unitId = $this->unitService->getUnitId();

        $subunits = $this->unitService->getSubunitPairs($unitId);
        $subunits = array_map(
            function (string $name) {
                return '» ' . $name;
            },
            $subunits
        );

        $units  = [
            $unitId => $this->unitService->getDetailV2($unitId)->getSortName(),
        ];
        $units += $subunits;

        $form = new BaseForm();
        $form->addText('name', 'Název akce*')
            ->addRule(Form::FILLED, 'Musíte vyplnit název akce');
        $form->addDatePicker('start', 'Od*')
            ->addRule(Form::FILLED, 'Musíte vyplnit začátek akce')
            ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.');
        $form->addDatePicker('end', 'Do*')
            ->addRule(Form::FILLED, 'Musíte vyplnit konec akce')
            ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.')
            ->addRule([\MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText('location', 'Místo');
        $form->addSelect('orgID', 'Pořádající jednotka', $units);
        $form->addSelect('scope', 'Rozsah (+)', $scopes)
            ->setDefaultValue('2');
        $form->addSelect('type', 'Typ (+)', $types)
            ->setDefaultValue('2');
        $form->addSubmit('send', 'Založit novou akci')
            ->setAttribute('class', 'btn btn-primary btn-large, ui--createEvent');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formCreateSubmitted($form);
        };

        return $form;
    }

    private function formCreateSubmitted(Form $form) : void
    {
        if (! $this->authorizator->isAllowed(Event::CREATE, null)) {
            $this->flashMessage('Nemáte oprávnění pro založení akce', 'danger');
            $this->redirect('this');
        }

        $v = $form->getValues();

        $startDate = Date::instance($v['start']);
        $endDate   = Date::instance($v['end']);

        if ($startDate > $endDate) {
            $form['start']->addError('Akce nemůže dříve začít než zkončit!');
            $this->redirect('this');
        }

        $this->commandBus->handle(
            new CreateEvent(
                $v['name'],
                $startDate,
                $endDate,
                $v->orgID,
                $v['location'] !== '' ? $v['location'] : null,
                $v['scope'],
                $v['type']
            )
        );

        $this->redirect(
            'Event:',
            [
            'aid' => $this->queryBus->handle(new NewestEventId()),
            ]
        );
    }
}
