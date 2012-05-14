<?php

/**
 * @author Hána František
 * akce
 */
class Accountancy_EventPresenter extends Accountancy_BasePresenter {

    public $ses;

    const DEFAULT_STATE = "draft";

    function startup() {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (!isset($this->ses->state))
            $this->ses->state = self::DEFAULT_STATE;
    }

    public function renderDefault() {
        $year = isset($this->ses->year) ? $this->ses->year : NULL;
        $state = isset($this->ses->state) ? $this->ses->state : NULL;
        if ($state == "all")
            $state = NULL;
        $list = $this->context->eventService->getAll($year, $state);
        foreach ($list as $key => $value) {
            $value->accessDelete = $this->context->userService->actionVerify($value->ID, "EV_EventGeneral_DELETE");
            $value->accessDetail = $this->context->userService->actionVerify($value->ID, "EV_EventGeneral_DETAIL");
            $list[$key] = $value;
        }
        $this->template->list = $list;
        if ($year)
            $this['formFilter']['year']->setDefaultValue($year);
        if ($state)
            $this['formFilter']['state']->setDefaultValue($state);

        $this->template->accessCreateEvent = $this->context->userService->actionVerify(NULL, "EV_EventGeneral_INSERT");
    }

    public function renderInfo($aid) {
        $data = $this->context->eventService->get($aid);

        //nastavení dat do formuláře pro editaci
        $func = false;
        if (array_key_exists("EV_EventFunction_ALL_EventGeneral", $this->availableEventActions))
            $func = $this->context->eventService->getFunctions($aid);

        $accessEditBase =
                array_key_exists("EV_EventGeneral_UPDATE", $this->availableEventActions) &&
                array_key_exists("EV_EventGeneral_UPDATE_Function", $this->availableEventActions);
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
        $this->template->isEditable = $this->context->eventService->isEditable($data);
        $this->template->accessEditBase = $accessEditBase;
        $this->template->accessCloseEvent = array_key_exists("EV_EventGeneral_UPDATE_Close", $this->availableEventActions);
        $this->template->accessOpenEvent = array_key_exists("EV_EventGeneral_UPDATE_Open", $this->availableEventActions);
        $this->template->accessDetailEvent = array_key_exists("EV_EventGeneral_DETAIL", $this->availableEventActions);
    }

    public function handleOpen($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Open", $this->availableEventActions)) {
            $this->flashMessage("Nemáte právo otevřít akci", "warning");
            $this->redirect("this");
        }
        $res = $this->context->eventService->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("this");
    }

    public function handleClose($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Close", $this->availableEventActions)) {
            $this->flashMessage("Nemáte právo akci uzavřít", "warning");
            $this->redirect("this");
        }
        if ($this->context->eventService->isCloseable($aid)) {
            $res = $this->context->eventService->close($aid);
            $this->flashMessage("Akce byla uzavřena.");
        } else {
            $this->flashMessage("Před uzavřením akce musí být vyplněn vedoucí akce", "danger");
        }
        $this->redirect("this");
    }

    public function renderReport($aid) {
        if (!array_key_exists("EV_EventGeneral_DETAIL", $this->availableEventActions)) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("info", array("aid" => $aid));
        }
        $actionInfo = $this->context->eventService->get($aid);
        $participants = $this->context->participantService->getAllParticipant($aid);
        $chitsAll = $this->context->chitService->getAll($aid);

        //inicializuje pole s kategorií s částkami na 0
        foreach (ArrayHash::from($this->context->chitService->getCategories($all = TRUE)) as $c) {
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
        $template->personsDays = $this->context->participantService->getPersonsDays($this->aid);
        $template->a = $actionInfo;
        $template->chits = $categories;
        $template->func = $this->context->eventService->getFunctions($aid);

        $this->context->participantService->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_report.pdf");
        $this->terminate();
    }

    public function handleCancel($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Cancel", $this->availableEventActions)) {
            $this->flashMessage("Nemáte právo na zrušení akce.", "danger");
            $this->redirect("this");
        }

        if ($this->context->eventService->cancel($aid)) {
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "danger");
        }

        $this->redirect("this");
    }

//    public function handleChangeYear($year) {
//        $this->ses->year = $year;
//        if ($this->isAjax()) {
//            $this->invalidateControl("events");
//        } else {
//            $this->redirect("this");
//        }
//    }
//
//    public function handleChangeState($state) {
//        $this->ses->state = $state;
//        if ($this->isAjax()) {
//            $this->invalidateControl("events");
//        } else {
//            $this->redirect("this");
//        }
//    }

    function createComponentFormFilter($name) {
        $states = array_merge(array("all" => "Nezrušené"), $this->context->eventService->getStates());
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
        $combo = $this->context->userService->getCombobox();
        $scopes = $this->context->eventService->getScopes();
        $types = $this->context->eventService->getTypes();


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
        if (!array_key_exists("EV_EventGeneral_INSERT", $this->availableEventActions)) {
            $this->flashMessage("Nemáte oprávnění pro založení akce", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();
        $id = $this->context->eventService->create(
                $v['name'], $v['start']->format("Y-m-d"), $v['end']->format("Y-m-d"), $v['location'], $v['leader'], $v['assistant'], $v['economist'], $unit = NULL, $v['scope'], $v['type']
        );

        if ($id) {
            $this->redirect("info", array("aid" => $id));
        }
        $this->redirect("this");
    }

    function createComponentFormEdit($name) {
        $combo = $this->context->userService->getCombobox(NULL, TRUE);

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
        if (!array_key_exists("EV_EventGeneral_UPDATE ", $this->availableEventActions)) {
            $this->flashMessage("Nemáte oprávnění pro úpravu akce", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        try {
            $id = $this->context->eventService->update($values);
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

