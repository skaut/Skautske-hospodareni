<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter
{

    public $ses;

    const DEFAULT_STATE = "approvedParent"; //filtrovani zobrazených položek

    protected function startup() : void
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

    public function renderDefault() : void
    {
        //filtrovani zobrazených položek
        $year = isset($this->ses->year) ? $this->ses->year : date("Y");
        $state = isset($this->ses->state) ? $this->ses->state : NULL;

        $list = $this->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $value['accessDetail'] = $this->userService->actionVerify(self::STable, $value['ID'], self::STable . "_DETAIL");
            $list[$key] = $value;
        }
        $this->template->list = $list;
        if ($year) {
            $this['formFilter']['year']->setDefaultValue($year);
        }
        if ($state) {
            $this['formFilter']['state']->setDefaultValue($state);
        }

        //$this->template->accessCreate = $this->isAllowed("EV_EventGeneral_INSERT");
    }

    public function handleChangeYear($year) : void
    {
        $this->ses->year = $year;
        if ($this->isAjax()) {
            $this->invalidateControl("camps");
        } else {
            $this->redirect("this");
        }
    }

    public function handleChangeState($state) : void
    {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->invalidateControl("camps");
        } else {
            $this->redirect("this");
        }
    }

    protected function createComponentFormFilter($name) : Form
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
