<?php

namespace AccountancyModule\EventModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František
 * akce
 */
class EventPresenter extends BasePresenter {

    public function renderDefault($aid) {
        if ($aid == NULL) {
            $this->redirect("Default:");
        }
        //nastavení dat do formuláře pro editaci
        $func = false;

        if ($this->isAllowed("EV_EventFunction_ALL_EventGeneral"))
            $func = $this->context->eventService->event->getFunctions($aid);

        $accessEditBase = $this->isAllowed("EV_EventGeneral_UPDATE");
//        && $this->isAllowed("EV_EventGeneral_UPDATE_Function");
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
        $this->template->isEditable = $this->context->eventService->event->isCommandEditable($this->event);
        $this->template->accessEditBase = $accessEditBase;
        $this->template->accessCloseEvent = $this->isAllowed("EV_EventGeneral_UPDATE_Close");
        $this->template->accessOpenEvent = $this->isAllowed("EV_EventGeneral_UPDATE_Open");
        $this->template->accessDetailEvent = $this->isAllowed("EV_EventGeneral_DETAIL");
        if($this->isAjax()){
            $this->invalidateControl("contentSnip");
        }
    }

    public function handleOpen($aid) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Open")) {
            $this->flashMessage("Nemáte právo otevřít akci", "warning");
            $this->redirect("this");
        }
        $this->context->eventService->event->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("this");
    }

    public function handleClose($aid) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Close")) {
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

    public function actionPrintAll($aid){
        $chits = (array)$this->context->eventService->chits->getAll($this->aid);

        $template = (string)$this->context->exportService->getEventReport($aid, $this->context->eventService) . $this->context->exportService->getNewPage();
        $template .= (string)$this->context->exportService->getParticipants($aid, $this->context->eventService) . $this->context->exportService->getNewPage();
//        $template .= (string)$this->context->exportService->getHpd($aid, $this->context->eventService, $this->context->unitService) . $this->context->exportService->getNewPage();
        $template .= (string)$this->context->exportService->getCashbook($aid, $this->context->eventService) . $this->context->exportService->getNewPage();
        $template .= (string)$this->context->exportService->getChits($aid, $this->context->eventService, $this->context->unitService, $chits);
        
        $this->context->eventService->participants->makePdf($template, "all.pdf");
        $this->terminate();
    }

    public function handleRemoveFunction($aid, $fid) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Function")) {
            $this->flashMessage("Nemáte oprávnění upravit vedení akce", "danger");
            $this->redirect("this");
        }

        if (!$this->context->eventService->event->setFunction($this->aid, NULL, $fid))
            $this->flashMessage("Funkci se nepodařilo odebrat", "danger");
        $this->redirect("this");
    }

    public function renderReport($aid) {
        if (!$this->isAllowed("EV_EventGeneral_DETAIL")) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        $template = $this->context->exportService->getEventReport($aid, $this->context->eventService);
        
        $this->context->eventService->participants->makePdf($template, "report.pdf");
        $this->terminate();
    }

    function createComponentFormEdit($name) {
//        $combo = $this->context->memberService->getCombobox(NULL, TRUE);
        $scopes = $this->context->eventService->event->getScopes();
        $types = $this->context->eventService->event->getTypes();

        $form = new Form($this, $name);
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

    function formEditSubmitted(Form $form) {

        if (!$this->isAllowed("EV_EventGeneral_UPDATE")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu akce", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        $id = $this->context->eventService->event->update($values);

        if ($id) {
            $this->flashMessage("Základní údaje byly upraveny.");
            $this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se upravit základní údaje", "danger");
        }
        $this->redirect("this");
    }

    function createComponentFormAddFunction($name) {
        $combo = $this->context->memberService->getCombobox(FALSE, TRUE);

        $form = new Form($this, $name);
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

    function formAddFunctionSubmitted(Form $form) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Function")) {
            $this->flashMessage("Nemáte oprávnění upravit vedení akce", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        try {
            $id = $this->context->eventService->event->setFunction($values['aid'], $values['person'], $values['fid']);
        } catch (\SkautIS\Exception\PermissionException $exc) {
            $this->flashMessage($exc->getMessage(), "danger");
            $this->redirect("default", array("aid" => $this->aid));
        } catch (\SkautIS\Exception\BaseException $e) {
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

