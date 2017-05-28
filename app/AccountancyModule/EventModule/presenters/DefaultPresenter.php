<?php

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Factories\GridFactory;
use MyValidators;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter
{

    const DEFAULT_STATE = "draft"; //filtrovani zobrazených položek

    public $ses;

    /** @var \Model\ExcelService */
    protected $excelService;

    /** @var GridFactory */
    protected $gridFactory;

    public function __construct(\Model\ExcelService $excel, GridFactory $gf)
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
        $grid->addColumnStatus('ID_EventGeneralState', 'Prefix')
            ->setCaret(FALSE)
            ->addOption("draft", 'Rozpracováno')->setClass('btn-warning')->endOption()
            ->addOption("closed", 'Uzavřeno')->setClass('btn-success')->endOption()
            ->addOption("cancelled", 'Zrušeno')->setClass('btn-invert')->endOption();
        
        $grid->addAction('delete', '', 'cancel!', ['aid' => 'ID'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirm('Opravdu chcete zrušit akci %s?', 'DisplayName');

        $grid->allowRowsAction('delete', function ($item) {
            if (!array_key_exists("accessDelete", $item)) {
                return TRUE;
            }
            return $item['accessDelete'];
        });
        return $grid;

    }

    public function renderDefault(): void
    {
        $this->template->accessCreate = $this->isAllowed("EV_EventGeneral_INSERT");
    }

    private function sortEvents(&$list, $param): void
    {
        switch ($param) {
            case 'name':
                $fnc = function ($a, $b) {
                    return strcasecmp($a['DisplayName'], $b['DisplayName']);
                };
                break;
            case 'end':
                $fnc = function ($a, $b) {
                    $aTime = strtotime($a['EndDate']);
                    $bTime = strtotime($b['EndDate']);
                    if ($aTime == $bTime) {
                        return strcasecmp($a['DisplayName'], $b['DisplayName']);
                    }
                    return $aTime > $bTime;
                };
                break;
            case 'prefix':
                $fnc = function ($a, $b) {
                    return strcasecmp($a['prefix'], $b['prefix']);
                };
                break;
            case 'state':
                $fnc = function ($a, $b) {
                    return strcasecmp($a['ID_EventGeneralState'], $b['ID_EventGeneralState']);
                };
                break;
            default:
                $fnc = function ($a, $b) {
                    $aTime = strtotime($a['StartDate']);
                    $bTime = strtotime($b['StartDate']);
                    if ($aTime == $bTime) {
                        return strcasecmp($a['DisplayName'], $b['DisplayName']);
                    }
                    return $aTime > $bTime;
                };
        }
        uasort($list, $fnc);
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
     * @param type $aid
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

    protected function createComponentFormFilter(string $name): Form
    {
        $states = array_merge(["all" => "Nezrušené"], $this->eventService->event->getStates());
        $years = ["all" => "Všechny"];
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }
        $form = $this->prepareForm($this, $name);
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

    protected function createComponentFormCreate($name): Form
    {
        $scopes = $this->eventService->event->getScopes();
        $types = $this->eventService->event->getTypes();
        $tmpId = $this->unitService->getUnitId();
        $units = [$tmpId => $this->unitService->getDetail($tmpId)->SortName];
        foreach ($this->unitService->getChild($tmpId) as $u) {
            $units[$u->ID] = "» " . $u->SortName;
        }

        $form = $this->prepareForm($this, $name);
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

    protected function createComponentFormExportSummary($name): Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addSubmit('send', 'Souhrn vybraných');

        $form->onSuccess[] = function (Form $form): void {
            $this->formExportSummarySubmitted($form);
        };

        return $form;
    }

    private function formExportSummarySubmitted(Form $form): void
    {
        $values = $form->getHttpData($form::DATA_TEXT, 'sel[]');
        $this->excelService->getEventSummaries($values, $this->eventService);
    }

}
