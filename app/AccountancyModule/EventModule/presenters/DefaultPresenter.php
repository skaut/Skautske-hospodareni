<?php

namespace App\AccountancyModule\EventModule;

use Nette\Application\UI\Form;
use MyValidators;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter {

    const DEFAULT_STATE = "draft"; //filtrovani zobrazených položek

    public $ses;

    /**
     *
     * @var \Model\ExcelService
     */
    protected $excelService;

    public function __construct(\Model\ExcelService $excel) {
        parent::__construct();
        $this->excelService = $excel;
    }

    function startup() {
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

    public function renderDefault($sort = 'start') {
        //filtrovani zobrazených položek
        $year = isset($this->ses->year) ? $this->ses->year : date("Y");
        $state = isset($this->ses->state) ? $this->ses->state : NULL;

        $list = $this->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $localAvaibleActions = $this->userService->actionVerify(self::STable, $value['ID']);
            $list[$key]['accessDelete'] = $this->isAllowed("EV_EventGeneral_DELETE", $localAvaibleActions);
            $list[$key]['accessDetail'] = $this->isAllowed("EV_EventGeneral_DETAIL", $localAvaibleActions);
        }
        $this->sortEvents($list, $sort);

        $this->template->list = $list;
        if ($year) {
            $this['formFilter']['year']->setDefaultValue($year);
        }
        if ($state) {
            $this['formFilter']['state']->setDefaultValue($state);
        }

        $this->template->accessCreate = $this->isAllowed("EV_EventGeneral_INSERT");
        $this->template->sort = $sort;
    }

    protected function sortEvents(&$list, $param) {
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
        uasort($list, $fnc
//                function ($a, $b) use ($fnc) {
//            $at = strtotime($a[$sortParam]);
//            $bt = strtotime($b[$sortParam]);
//            return ($at == $bt) ? strcasecmp($a['DisplayName'], $b['DisplayName']) : ($fnc ? 1 : -1);
//        }
        );
    }

    /**
     * mění podmínky filtrování akcí podle roku
     * @param type $year 
     */
    public function handleChangeYear($year) {
        $this->ses->year = $year;
        if ($this->isAjax()) {
            $this->redrawControl('events');
        } else {
            $this->redirect("this");
        }
    }

    /**
     * změní podmínky filtrování akcí podle stavu akce
     * @param type $state 
     */
    public function handleChangeState($state) {
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
    public function handleCancel($aid) {
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

    function createComponentFormFilter($name) {
        $states = array_merge(array("all" => "Nezrušené"), $this->eventService->event->getStates());
        $years = array("all" => "Všechny");
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }
        $form = $this->prepareForm($this, $name);
        $form->addSelect("state", "Stav", $states);
        $form->addSelect("year", "Rok", $years);
        $form->addSubmit('send', 'Hledat')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');

        return $form;
    }

    function formFilterSubmitted(Form $form) {
        $v = $form->getValues();
        $this->ses->year = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect("default", array("aid" => $this->aid));
    }

    function isDateValidator($item, $args) {
        return $item == NULL ? FALSE : TRUE;
    }

    function createComponentFormCreate($name) {
        $scopes = $this->eventService->event->getScopes();
        $types = $this->eventService->event->getTypes();
        $tmpId = $this->unitService->getUnitId();
        $units = array($tmpId => $this->unitService->getDetail($tmpId)->SortName);
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
                ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.');
        $form->addText("location", "Místo");
        $form->addSelect("orgID", "Pořádající jednotka", $units);
        $form->addSelect("scope", "Rozsah (+)", $scopes)
                ->setDefaultValue("2");
        $form->addSelect("type", "Typ (+)", $types)
                ->setDefaultValue("2");
        $form->addSubmit('send', 'Založit novou akci')
                ->setAttribute("class", "btn btn-primary btn-large");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(Form $form) {
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
            } else {
                throw $e;
            }
        }

        if ($id) {
            $this->redirect("Event:", array("aid" => $id));
        }
        $this->redirect("this");
    }

    function createComponentFormExportSummary($name) {
        $form = $this->prepareForm($this, $name);
        $form->addSubmit('send', 'Souhrn vybraných');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formExportSummarySubmitted(Form $form) {
        $values = $form->getHttpData($form::DATA_TEXT, 'sel[]');
        $this->excelService->getEventSummaries($values, $this->eventService);
    }

}
