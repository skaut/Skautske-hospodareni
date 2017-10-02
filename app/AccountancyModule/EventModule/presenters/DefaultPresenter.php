<?php

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Factories\GridFactory;
use App\Forms\BaseForm;
use Model\ExcelService;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;
use MyValidators;

class DefaultPresenter extends BasePresenter
{

    const DEFAULT_STATE = "draft"; //filtrovani zobrazených položek

    public $ses;

    /** @var ExcelService */
    private $excelService;

    /** @var GridFactory */
    private $gridFactory;

    public function __construct(ExcelService $excel, GridFactory $gf)
    {
        parent::__construct();
        $this->excelService = $excel;
        $this->gridFactory = $gf;
    }

    protected function startup(): void
    {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (!isset($this->ses->state)) {
            $this->ses->state = self::DEFAULT_STATE;
        }
        if (!isset($this->ses->year)) {
            $this->ses->year = date("Y");
        }
    }

    protected function createComponentEventGrid()
    {
        //filtrovani zobrazených položek
        $year = $this->ses->year ?? date('Y');
        $state = $this->ses->state ?? NULL;
        $list = $this->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $localAvaibleActions = $this->userService->actionVerify(self::STable, $value['ID']);
            $list[$key]['accessDelete'] = $this->isAllowed("EV_EventGeneral_DELETE", $localAvaibleActions);
            $list[$key]['accessDetail'] = $this->isAllowed("EV_EventGeneral_DETAIL", $localAvaibleActions);
        }

        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey("ID");
        $grid->setDataSource($list);
        $grid->addColumnLink('DisplayName', 'Název', 'Event:default', NULL, ['aid' => 'ID'])->setSortable()->setFilterText();
        $grid->addColumnDateTime('StartDate', 'Od')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnDateTime('EndDate', 'Do')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnText('prefix', 'Prefix')->setSortable();
        $grid->addColumnText('state', 'Stav');

        $grid->addAction('delete', '', 'cancel!', ['aid' => 'ID'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirm('Opravdu chcete zrušit akci %s?', 'DisplayName');

        $grid->addGroupAction('Souhrn akcí')->onSelect[] = function(array $ids) {
            $this->redirect('exportEvents!', ['ids' => $ids]);
        };

        $grid->allowRowsAction('delete', function ($item) {
            if (!array_key_exists("accessDelete", $item)) {
                return TRUE;
            }
            return $item['accessDelete'];
        });

        $grid->setTemplateFile(__DIR__ . "/../templates/eventsGrid.latte");
        return $grid;
    }

    public function renderDefault(): void
    {
        $this['formFilter']['state']->setDefaultValue($this->ses->state);
        $this['formFilter']['year']->setDefaultValue($this->ses->year);
        $this->template->accessCreate = $this->isAllowed("EV_EventGeneral_INSERT");
    }

    public function handleExportEvents(array $ids): void
    {
        $ids = array_map('intval', $ids);
        $this->excelService->getEventSummaries($ids, $this->eventService);
    }

    public function handleChangeYear(?int $year): void
    {
        $this->ses->year = $year;
        if ($this->isAjax()) {
            $this->redrawControl('events');
        } else {
            $this->redirect("this");
        }
    }

    public function handleChangeState(?string $state): void
    {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->redrawControl('events');
        } else {
            $this->redirect("this");
        }
    }

    /**
     * zruší akci
     * @param int $aid
     */
    public function handleCancel(int $aid): void
    {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Cancel")) {
            $this->flashMessage("Nemáte právo na zrušení akce.", "danger");
            $this->redirect("this");
        }

        if ($this->eventService->event->cancel($aid, $this->eventService->chits)) {
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "danger");
        }

        $this->redirect("this");
    }

    protected function createComponentFormFilter(): Form
    {
        $states = array_merge(["all" => "Nezrušené"], $this->eventService->event->getStates());
        $years = ["all" => "Všechny"];
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }
        $form = new BaseForm();
        $form->addSelect("state", "Stav", $states);
        $form->addSelect("year", "Rok", $years);
        $form->addSubmit('send', 'Hledat')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function (Form $form): void {
            $this->formFilterSubmitted($form);
        };

        return $form;
    }

    private function formFilterSubmitted(Form $form): void
    {
        $v = $form->getValues();
        $this->ses->year = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect("default", ["aid" => $this->aid]);
    }

    public function isDateValidator($item, $args)
    {
        return $item == NULL ? FALSE : TRUE;
    }

    protected function createComponentFormCreate(): Form
    {
        $scopes = $this->eventService->event->getScopes();
        $types = $this->eventService->event->getTypes();
        $tmpId = $this->unitService->getUnitId();
        $units = [$tmpId => $this->unitService->getDetail($tmpId)->SortName];
        foreach ($this->unitService->getChild($tmpId) as $u) {
            $units[$u->getId()] = "» " . $u->getSortName();
        }

        $form = new BaseForm();
        $form->addText("name", "Název akce*")
            ->addRule(Form::FILLED, "Musíte vyplnit název akce");
        $form->addDatePicker("start", "Od*")
            ->addRule(Form::FILLED, "Musíte vyplnit začátek akce")
            ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.');
        $form->addDatePicker("end", "Do*")
            ->addRule(Form::FILLED, "Musíte vyplnit konec akce")
            ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.')
            ->addRule([\MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText("location", "Místo");
        $form->addSelect("orgID", "Pořádající jednotka", $units);
        $form->addSelect("scope", "Rozsah (+)", $scopes)
            ->setDefaultValue("2");
        $form->addSelect("type", "Typ (+)", $types)
            ->setDefaultValue("2");
        $form->addSubmit('send', 'Založit novou akci')
            ->setAttribute("class", "btn btn-primary btn-large");

        $form->onSuccess[] = function (Form $form): void {
            $this->formCreateSubmitted($form);
        };

        return $form;
    }

    private function formCreateSubmitted(Form $form): void
    {
        if (!$this->isAllowed("EV_EventGeneral_INSERT")) {
            $this->flashMessage("Nemáte oprávnění pro založení akce", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();
        try {
            $id = $this->eventService->event->create(
                $v['name'], $v['start']->format("Y-m-d"), $v['end']->format("Y-m-d"), $v['location'], $v->orgID, $v['scope'], $v['type']
            );
        } catch (\Skautis\Wsdl\WsdlException $e) {
            if (strpos("EventGeneral_EndLesserThanStart", $e->getMessage())) {
                $form['start']->addError("Akce nemůže dříve začít než zkončit!");
                return;
            }
            throw $e;
        }

        if ($id) {
            $this->redirect("Event:", ["aid" => $id]);
        }
        $this->redirect("this");
    }

}
