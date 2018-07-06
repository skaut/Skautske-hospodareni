<?php

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\Factories\GridFactory;
use App\Forms\BaseForm;
use Model\Auth\Resources\Camp;
use Model\Event\ReadModel\Queries\CampStates;
use Model\ExcelService;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;

class DefaultPresenter extends BasePresenter
{

    public $ses;

    const DEFAULT_STATE = "approvedParent"; //filtrovani zobrazených položek

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


    protected function startup() : void
    {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if(!isset($this->ses->state)) {
            $this->ses->state = self::DEFAULT_STATE;
        }
        if(!isset($this->ses->year)) {
            $this->ses->year = date("Y");
        }
    }

    protected function createComponentCampGrid() : DataGrid
    {
        //filtrovani zobrazených položek
        $year = $this->ses->year ?? date('Y');
        $state = $this->ses->state ?? NULL;
        $list = $this->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $list[$key]['accessDetail'] = $this->authorizator->isAllowed(Camp::ACCESS_DETAIL, $value['ID']);
        }

        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey("ID");
        $grid->setDataSource($list);
        $grid->addColumnLink('DisplayName', 'Název', 'Detail:default', NULL, ['aid' => 'ID'])->setSortable()->setFilterText();
        $grid->addColumnDateTime('StartDate', 'Od')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnDateTime('EndDate', 'Do')->setFormat('d.m.Y')->setSortable();
        $grid->addColumnText('Location', 'Místo konání')->setSortable()->setFilterText();
        $grid->addColumnText('state', 'Stav');

        $grid->setTemplateFile(__DIR__ . "/../templates/campsGrid.latte");
        return $grid;
    }


    public function renderDefault() : void
    {
        if($this->ses->year !== NULL) {
            $this['formFilter']['year']->setDefaultValue($this->ses->year);
        }
        if($this->ses->state !== NULL) {
            $this['formFilter']['state']->setDefaultValue($this->ses->state);
        }
    }

    public function actionCampSummary() : void
    {
        $this->excelService->getCampsSummary(array_keys($this->eventService->event->getAll($this->ses->year, $this->ses->state)), $this->eventService, $this->unitService);
        $this->terminate();
    }

    public function handleChangeYear(?int $year) : void
    {
        $this->ses->year = $year;
        if($this->isAjax()) {
            $this->redrawControl("camps");
        } else {
            $this->redirect("this");
        }
    }

    public function handleChangeState($state) : void
    {
        $this->ses->state = $state;
        if($this->isAjax()) {
            $this->redrawControl("camps");
        } else {
            $this->redirect("this");
        }
    }

    protected function createComponentFormFilter($name) : Form
    {
        $states = array_merge(["all" => "Nezrušené"], $this->queryBus->handle(new CampStates()));
        $years = ["all" => "Všechny"];
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }

        $form = new BaseForm();
        $form->addSelect("state", "Stav", $states);
        $form->addSelect("year", "Rok", $years);
        $form->addSubmit('send', 'Hledat')
            ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = function(Form $form) : void {
            $this->formFilterSubmitted($form);
        };

        return $form;
    }

    private function formFilterSubmitted(Form $form) : void
    {
        $v = $form->getValues();
        $this->ses->year = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect("default", ["aid" => $this->aid]);
    }

}
