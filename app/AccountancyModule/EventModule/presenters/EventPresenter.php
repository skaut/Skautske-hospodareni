<?php

/**
 * @author Hána František
 * akce
 */
class Accountancy_Event_EventPresenter extends Accountancy_Event_BasePresenter  {

    public $ses;

    const DEFAULT_STATE = "draft"; //filtrovani zobrazených položek

    function startup() {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (!isset($this->ses->state))
            $this->ses->state = self::DEFAULT_STATE;
    }

    public function renderDefault() {
        //filtrovani zobrazených položek
        $year = isset($this->ses->year) ? $this->ses->year : NULL;
        $state = isset($this->ses->state) ? $this->ses->state : NULL;
        if ($state == "all")
            $state = NULL;
        $list = $this->context->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $localAvaibleActions = $this->context->userService->actionVerify(self::STable, $value->ID);
            $value->accessDelete = array_key_exists("EV_EventGeneral_DELETE", $localAvaibleActions);
            $value->accessDetail = array_key_exists("EV_EventGeneral_DETAIL", $localAvaibleActions);
            $list[$key] = $value;
        }
        $this->template->list = $list;
        if ($year)
            $this['formFilter']['year']->setDefaultValue($year);
        if ($state)
            $this['formFilter']['state']->setDefaultValue($state);

        $this->template->accessCreate = array_key_exists("EV_EventGeneral_INSERT", $this->availableActions);
    }

    public function renderInfo($aid) {
        $data = $this->context->eventService->event->get($aid);

        //nastavení dat do formuláře pro editaci
        $func = false;
        if (array_key_exists("EV_EventFunction_ALL_EventGeneral", $this->availableActions))
            $func = $this->context->eventService->event->getFunctions($aid);

        $accessEditBase =
                array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions) &&
                array_key_exists("EV_EventGeneral_UPDATE_Function", $this->availableActions);
        if ($accessEditBase) {
            $form = $this['formEdit'];
            $form->setDefaults(array(
                "aid" => $aid,
                "name" => $data->DisplayName,
                "start" => $data->StartDate,
                "end" => $data->EndDate,
                "location" => $data->Location,
                "leader" => isset($func) && is_array($func) ? $func[EventService::LEADER]->ID_Person : "",
                "assistant" => isset($func) && is_array($func) ? $func[EventService::ASSISTANT]->ID_Person : "",
                "economist" => isset($func) && is_array($func) ? $func[EventService::ECONOMIST]->ID_Person : "",
            ));
        }
        $this->template->data = $data;
        $this->template->funkce = $func;
        $this->template->isEditable = $this->context->eventService->event->isEditable($data);
        $this->template->accessEditBase = $accessEditBase;
        $this->template->accessCloseEvent = array_key_exists("EV_EventGeneral_UPDATE_Close", $this->availableActions);
        $this->template->accessOpenEvent = array_key_exists("EV_EventGeneral_UPDATE_Open", $this->availableActions);
        $this->template->accessDetailEvent = array_key_exists("EV_EventGeneral_DETAIL", $this->availableActions);
    }

    public function handleOpen($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Open", $this->availableActions)) {
            $this->flashMessage("Nemáte právo otevřít akci", "warning");
            $this->redirect("this");
        }
        $this->context->eventService->event->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("this");
    }

    public function handleClose($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Close", $this->availableActions)) {
            $this->flashMessage("Nemáte právo akci uzavřít", "warning");
            $this->redirect("this");
        }
        if ($this->context->eventService->event->isCloseable($aid)) {
            $this->context->eventService->event->close($aid);
            $this->flashMessage("Akce byla uzavřena.");
        } else {
            $this->flashMessage("Před uzavřením akce musí být vyplněn vedoucí akce", "danger");
        }
        $this->redirect("this");
    }

    public function renderReport($aid) {
        if (!array_key_exists("EV_EventGeneral_DETAIL", $this->availableActions)) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("info", array("aid" => $aid));
        }
        $actionInfo = $this->context->eventService->event->get($aid);
        $participants = $this->context->eventService->participants->getAll($aid);
        $chitsAll = $this->context->eventService->chits->getAll($aid);

        //inicializuje pole s kategorií s částkami na 0
        foreach (ArrayHash::from($this->context->eventService->chits->getCategories($all = TRUE)) as $c) {
            $categories[$c->type][$c->short] = $c;
            $categories[$c->type][$c->short]->price = 0;
        }

        //rozpočítává paragony do jednotlivých skupin
        foreach ($chitsAll as $chit) {
            $categories[$chit->ctype][$chit->cshort]->price += $chit->price;
        }

        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Event/report.latte');
        $template->registerHelper('price', 'AccountancyHelpers::price');
        $template->participants = $participants;
        $template->personsDays = $this->context->eventService->participants->getPersonsDays($this->aid);
        $template->a = $actionInfo;
        $template->chits = $categories;
        $template->func = $this->context->eventService->event->getFunctions($aid);

        $this->context->eventService->participants->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_report.pdf");
        $this->terminate();
    }

    public function handleCancel($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Cancel", $this->availableActions)) {
            $this->flashMessage("Nemáte právo na zrušení akce.", "danger");
            $this->redirect("this");
        }

        if ($this->context->eventService->event->cancel($aid)) {
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "danger");
        }

        $this->redirect("this");
    }

    public function handleChangeYear($year) {
        $this->ses->year = $year;
        if ($this->isAjax()) {
            $this->invalidateControl("events");
        } else {
            $this->redirect("this");
        }
    }

    public function handleChangeState($state) {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->invalidateControl("events");
        } else {
            $this->redirect("this");
        }
    }

    function createComponentFormFilter($name) {
        $states = array_merge(array("all" => "Nezrušené"), $this->context->eventService->event->getStates());
        $years = array();
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }

        $form = new AppForm($this, $name);
        $form->addSelect("state", "Stav", $states);
        $form->addSelect("year", "Rok", $years);
        $form->addSubmit('send', 'Hledat')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');

        return $form;
    }

    function formFilterSubmitted(AppForm $form) {
        $v = $form->getValues();
        $this->ses->year = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect("default", array("aid" => $this->aid));
    }

    function createComponentFormCreate($name) {
        $combo = $this->context->memberService->getCombobox();
        $scopes = $this->context->eventService->event->getScopes();
        $types = $this->context->eventService->event->getTypes();


        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce")
                ->addRule(Form::FILLED, "Musíte vyplnit název akce");
        $form->addDatePicker("start", "Od")
                ->addRule(Form::FILLED, "Musíte vyplnit začátek akce");
        $form->addDatePicker("end", "Do")
                ->addRule(Form::FILLED, "Musíte vyplnit konec akce");
        $form->addText("location", "Místo");
        $form->addSelect("scope", "Rozsah", $scopes)
                ->setDefaultValue("2");
        $form->addSelect("type", "Rozsah", $types)
                ->setDefaultValue("2");
        $form->addSelect("leader", "Vedoucí akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("assistant", "Zástupce ved. akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("economist", "Hospodář", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSubmit('send', 'Založit akci')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(AppForm $form) {
        if (!array_key_exists("EV_EventGeneral_INSERT", $this->availableActions)) {
            $this->flashMessage("Nemáte oprávnění pro založení akce", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();
        $id = $this->context->eventService->event->create(
                $v['name'], $v['start']->format("Y-m-d"), $v['end']->format("Y-m-d"), $v['location'], $v['leader'], $v['assistant'], $v['economist'], $unit = NULL, $v['scope'], $v['type']
        );

        if ($id) {
            $this->redirect("info", array("aid" => $id));
        }
        $this->redirect("this");
    }

    function createComponentFormEdit($name) {
        $combo = $this->context->memberService->getCombobox(NULL, TRUE);

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od");
        $form->addDatePicker("end", "Do");
        $form->addText("location", "Místo");
        $form->addSelect("leader", "Vedoucí akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("assistant", "Zástupce ved. akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("economist", "Hospodář", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-primary");

        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditSubmitted(AppForm $form) {
        
        if (!array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions)) {
            $this->flashMessage("Nemáte oprávnění pro úpravu akce", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        try {
            $id = $this->context->eventService->event->update($values);
        } catch (SkautIS_PermissionException $exc) {
            $this->flashMessage($exc->getMessage(), "danger");
            $this->redirect("Action:");
        } catch (SkautIS_Exception $e) {
            if (preg_match("/EventFunction_LeaderMustBeAdult/", $e->getMessage())) {//dospělost vedoucího akce
                $this->flashMessage("Vedoucí akce musí být dosplělá osoba.", "danger");
            } elseif (preg_match("/EventFunction_AssistantMustBeAdult/", $e->getMessage())) { //dospělost zástupce
                $this->flashMessage("Zástupce musí být dosplělá osoba.", "danger");
            } else {
                throw $e;
            }
            $this->redirect("this");
        }

        if ($id) {
            $this->flashMessage("Základní údaje byly upraveny.");
            $this->redirect("info", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se upravit základní údaje", "danger");
        }
        $this->redirect("this");
    }

}

