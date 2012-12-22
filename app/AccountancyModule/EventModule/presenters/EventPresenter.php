<?php

/**
 * @author Hána František
 * akce
 */
class Accountancy_Event_EventPresenter extends Accountancy_Event_BasePresenter {

    public function renderDefault($aid) {
        //nastavení dat do formuláře pro editaci
        $func = false;
        if (array_key_exists("EV_EventFunction_ALL_EventGeneral", $this->availableActions))
            $func = $this->context->eventService->event->getFunctions($aid);

        $accessEditBase = array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions);
//        && array_key_exists("EV_EventGeneral_UPDATE_Function", $this->availableActions);
        if ($accessEditBase) {
            $form = $this['formEdit'];
            $form->setDefaults(array(
                "aid" => $aid,
                "name" => $this->event->DisplayName,
                "start" => $this->event->StartDate,
                "end" => $this->event->EndDate,
                "location" => $this->event->Location,
                "type" => $this->event->ID_EventGeneralType,
                "scope" => $this->event->ID_EventGeneralScope,
//                "leader" => isset($func) && is_array($func) ? $func[EventService::LEADER]->ID_Person : "",
//                "assistant" => isset($func) && is_array($func) ? $func[EventService::ASSISTANT]->ID_Person : "",
//                "economist" => isset($func) && is_array($func) ? $func[EventService::ECONOMIST]->ID_Person : "",
            ));
        }
        $this->template->funkce = $func;
        $this->template->isEditable = $this->context->eventService->event->isEditable($this->event);
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

    public function actionAddFunction($aid, $fid) {
        $form = $this['formAddFunction'];
        $form->setDefaults(array(
            "aid" => $aid,
            "fid" => $fid,
        ));
    }

    public function actionEditFunction($aid, $fid) {
        $this->template->setFile(dirname(__FILE__) . "/../templates/Event/addFunction.latte");
        $func = $this->context->eventService->event->getFunctions($aid);
//        dump($func);die();
        $form = $this['formAddFunction'];
        $form->setDefaults(array(
            "aid" => $aid,
            "person" => $func[$fid]->ID_Person,
            "fid" => $fid,
        ));
    }

    public function handleRemoveFunction($aid, $fid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Function", $this->availableActions)) {
            $this->flashMessage("Nemáte oprávnění upravit vedení akce", "danger");
            $this->redirect("this");
        }

        if (!$this->context->eventService->event->setFunction($this->aid, NULL, $fid))
            $this->flashMessage("Funkci se nepodařilo odebrat", "danger");
        $this->redirect("this");
    }

    public function renderReport($aid) {
        if (!array_key_exists("EV_EventGeneral_DETAIL", $this->availableActions)) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        $actionInfo = $this->context->eventService->event->get($aid);
        $participants = $this->context->eventService->participants->getAll($aid);
        $chitsAll = $this->context->eventService->chits->getAll($aid);

        $categories = array();
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

    function createComponentFormEdit($name) {
//        $combo = $this->context->memberService->getCombobox(NULL, TRUE);
        $scopes = $this->context->eventService->event->getScopes();
        $types = $this->context->eventService->event->getTypes();

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od");
        $form->addDatePicker("end", "Do");
        $form->addText("location", "Místo");
        $form->addSelect("type", "Typ (+)", $types);
        $form->addSelect("scope", "Rozsah (+)", $scopes);
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

//        try {
        $id = $this->context->eventService->event->update($values);
//        } catch (SkautIS_PermissionException $exc) {
//            $this->flashMessage($exc->getMessage(), "danger");
//            $this->redirect("Action:");
//        } catch (SkautIS_Exception $e) {
//            if (preg_match("/EventFunction_LeaderMustBeAdult/", $e->getMessage())) {//dospělost vedoucího akce
//                $this->flashMessage("Vedoucí akce musí být dosplělá osoba.", "danger");
//            } elseif (preg_match("/EventFunction_AssistantMustBeAdult/", $e->getMessage())) { //dospělost zástupce
//                $this->flashMessage("Zástupce musí být dosplělá osoba.", "danger");
//            } else {
//                throw $e;
//            }
//            $this->redirect("this");
//        }

        if ($id) {
            $this->flashMessage("Základní údaje byly upraveny.");
            $this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se upravit základní údaje", "danger");
        }
        $this->redirect("this");
    }

    function createComponentFormAddFunction($name) {
        $combo = $this->context->memberService->getCombobox(NULL, TRUE);

        $form = new AppForm($this, $name);
        $form->addSelect("person", NULL, $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addHidden("fid");
        $form->addHidden("aid");
        $form->addSubmit('send', 'Přidat')
                ->getControlPrototype()->setClass("btn btn-primary");

        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formAddFunctionSubmitted(AppForm $form) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Function", $this->availableActions)) {
            $this->flashMessage("Nemáte oprávnění upravit vedení akce", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        try {
            $id = $this->context->eventService->event->setFunction($values['aid'], $values['person'], $values['fid']);
        } catch (SkautIS_PermissionException $exc) {
            $this->flashMessage($exc->getMessage(), "danger");
            $this->redirect("default", array("aid" => $this->aid));
        } catch (SkautIS_Exception $e) {
            if (preg_match("/EventFunction_LeaderMustBeAdult/", $e->getMessage())) {//dospělost vedoucího akce
                $this->flashMessage("Vedoucí akce musí být dosplělá osoba.", "danger");
            } elseif (preg_match("/EventFunction_AssistantMustBeAdult/", $e->getMessage())) { //dospělost zástupce
                $this->flashMessage("Zástupce musí být dosplělá osoba.", "danger");
            } else {
                throw $e;
            }
            $this->redirect("Default", array("aid" => $this->aid));
        }

        if (!$id) {
            $this->flashMessage("Nepodařilo se upravit funkci", "danger");
        }
        $this->redirect("default", array("aid" => $this->aid));
    }

}

