<?php

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

trait ParticipantTrait {
    /**
     *
     * @var \Model\EventService
     */
    //protected $eventService;

    /**
     * číslo aktuální jendotky
     * @var int
     */
    protected $uid;
    protected $isAllowRepayment;
    protected $isAllowIsAccount;
    protected $isAllowParticipantInsert;
    protected $isAllowParticipantUpdate;
    protected $isAllowParticipantDelete;

    /**
     *
     * @var \Model\MemberService
     */
    protected $memberService;

    /**
     *
     * @var \Model\ExportService
     */
    protected $exportService;

    /**
     *
     * @var \Model\ExcelService
     */
    protected $excelService;

    protected function traitStartup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Nepovolený přístup", "danger");
            $this->redirect("Default:");
        }
        $this->uid = $this->getParameter("uid", NULL);
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    protected function traitDefault($dp, $sort, $regNums) {
        $participants = $this->eventService->participants->getAll($this->aid);
        try {
            $list = $dp ? array() : $this->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);
        } catch (\Skautis\Wsdl\WsdlException $e) {
            if (!$dp && strpos("Timeout expired", $e->getMessage())) {
                $this->flashMessage("Bylo vypnuto doplňování osob, protože vypršel časový limit!", 'danger');
                $this->redirect("this", array("aid" => $this->aid, "uid" => $this->uid, "dp" => 1));
            }
            throw $e;
        }
        $this->sortParticipants($participants, $sort);
        $this->template->list = $list;
        $this->template->participants = $participants;

        $this->template->unit = $unit = $this->unitService->getDetail($this->uid);
        $this->template->uparrent = $this->unitService->getParrent($unit->ID);
        $this->template->uchildrens = $this->unitService->getChild($unit->ID);
        $this->template->sort = $sort;
        $this->template->useRegNums = $regNums;
    }

    protected function sortParticipants(&$participants, $sort) {
        $textItems = array("regNum", "isAccount");
        $numberItems = array("Days", "payment", "repayment");
        if (count($participants) > 0) {
            if($sort == "regNum"){
                $sort = "UnitRegistrationNumber";
            } elseif ($sort === NULL || !in_array($sort, array_merge($textItems, $numberItems)) || !property_exists($participants[0], $sort)) {
                $sort = "Person";//default sort
            }
            $isNumeric = in_array($sort, $numberItems);
            usort($participants, function ($a, $b) use ($sort, $isNumeric) {
                return $isNumeric ? $a->{$sort} > $b->{$sort} : strcasecmp($a->{$sort}, $b->{$sort});
            });
        }
    }

    public function actionExport($aid) {
        $type = $this->eventService->participants->type; //camp vs general
        try {
            $template = $this->exportService->getParticipants($this->createTemplate(), $aid, $this->eventService, $type);
            //echo $template;die();
            $this->eventService->participants->makePdf($template, "seznam-ucastniku.pdf", $type == "camp" ? TRUE : FALSE);
        } catch (\Skautis\Wsdl\PermissionException $ex) {
            $this->flashMessage("Nemáte oprávnění k záznamu osoby! (" . $ex->getMessage() . ")", "danger");
            $this->redirect("default", array("aid" => $this->aid));
        }
        $this->terminate();
    }

    public function actionExportExcel($aid) {
        $type = $this->eventService->participants->type; //camp vs general
        try {
            $this->excelService->getParticipants($this->eventService, $this->event, $type);
        } catch (\Skautis\Wsdl\PermissionException $ex) {
            $this->flashMessage("Nemáte oprávnění k záznamu osoby! (" . $ex->getMessage() . ")", "danger");
            $this->redirect("default", array("aid" => $aid));
        }
        $this->terminate();
    }

    public function handleRemove($pid) {
        if (!$this->isAllowParticipantDelete) {
            $this->flashMessage("Nemáte právo mazat účastníky.", "danger");
            $this->redirect("this");
        }
        $this->eventService->participants->removeParticipant($pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
//            $this->invalidateControl("flash");
        } else {
            $this->redirect('this');
        }
    }

    public function handleAdd($pid) {
        if (!$this->isAllowParticipantInsert) {
            $this->flashMessage("Nemáte oprávnění přidávat účastníky.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("this");
            }
        }
        $this->eventService->participants->add($this->aid, $pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('this');
        }
    }

    /**
     * mění stav jestli vypisovat pouze přímé členy
     */
    public function handleChangeDirectMemberOnly() {
        $this->setDirectMemberOnly(!$this->getDirectMemberOnly());
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
        } else {
            $this->redirect("this");
        }
    }

    public function createComponentFormMassList($name) {
        $form = $this->prepareForm($this, $name);
        $form->addSubmit('send')
                ->onClick[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formMassListSubmitted(SubmitButton $button) {
        if (!$this->isAllowParticipantInsert) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }
        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massList[]') as $id) {
            $this->eventService->participants->add($this->aid, $id);
        }
        $this->redirect("this");
    }

    public function createComponentFormMassParticipants($name) {
        $form = $this->prepareForm($this, $name);
        $form->addProtection();

        $editCon = $form->addContainer("edit");
        $editCon->addText("days", "Dní");
        $editCon->addText("payment", "Částka");
        $editCon->addText("repayment", "Vratka");
        $editCon->addRadioList("isAccount", "Na účet?", array("N" => "Ne", "Y" => "Ano"));
        $editCon->addCheckbox("daysc");
        $editCon->addCheckbox("paymentc");
        $editCon->addCheckbox("repaymentc");
        $editCon->addCheckbox("isAccountc"); //->setDefaultValue(TRUE);
        $editCon->addSubmit('send', 'Upravit')
                        ->setAttribute('class', 'btn btn-info btn-small')
                ->onClick[] = $this->massEditSubmitted;


        $form->addSubmit('send', 'Odebrat vybrané')
                ->onClick[] = $this->massRemoveSubmitted;
    }

    public function massEditSubmitted(SubmitButton $button) {
        $type = $this->eventService->participants->type; //camp vs general
        if (!$this->isAllowParticipantUpdate) {
            $this->flashMessage("Nemáte právo upravovat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $values = $button->getForm()->getValues();
        $data = array("actionId" => $this->aid);
        if ($values['edit']['daysc']) {
            $data['days'] = $values['edit']['days'];
        }
        if ($values['edit']['paymentc']) {
            $data['payment'] = $values['edit']['payment'];
        }
        if ($values['edit']['repaymentc']) {
            $data['repayment'] = $values['edit']['repayment'];
        }
        if ($values['edit']['isAccountc']) {
            $data['isAccount'] = $values['edit']['isAccount'];
        }

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $oldData = ($type == "camp") ? array() : $this->eventService->participants->get($id);
            $this->eventService->participants->update($id, array_merge((array) $oldData, $data));
        }
        $this->redirect("this");
    }

    public function massRemoveSubmitted(SubmitButton $button) {
        if (!$this->isAllowParticipantDelete) {
            $this->flashMessage("Nemáte právo mazat účastníky.", "danger");
            $this->redirect("Default:");
        }

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $this->eventService->participants->removeParticipant($id);
        }
        $this->redirect("this");
    }

    /**
     * formulář na přidání nové osoby
     * @param string $name
     * @return Form
     */
    function createComponentFormAddParticipantNew($name) {
        $aid = $this->presenter->aid;
        $form = $this->prepareForm($this, $name);
        $form->addText("firstName", "Jméno")
                ->addRule(Form::FILLED, "Musíš vyplnit křestní jméno.");
        $form->addText("lastName", "Příjmení")
                ->addRule(Form::FILLED, "Musíš vyplnit příjmení.");
        $form->addText("nick", "Přezdívka");
        $form->addText("birthday", "Dat. nar.");
        $form->addText("street", "Ulice");
        $form->addText("city", "Město");
        $form->addText("postcode", "PSČ");
        $form->addHidden("aid", $aid);
        $form->addSubmit('send', 'Založit účastníka')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formAddParticipantNewSubmitted(Form $form) {
        if (!$this->isAllowParticipantInsert) {
            $this->flashMessage("Nemáte oprávnění přidávat účastníky.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
        $values = $form->getValues();
        $aid = $values['aid'];
        $person = array(
            "firstName" => $values['firstName'],
            "lastName" => $values['lastName'],
            "nick" => $values['nick'],
            "Birthday" => date("c", strtotime($values['birthday'])),
            "street" => $values['street'],
            "city" => $values['city'],
            "postcode" => $values['postcode'],
        );
        $this->eventService->participants->addNew($aid, $person);
        $this->redirect("this");
    }

    protected function getDirectMemberOnly() {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct) {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }

}
