<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 * akce
 */
class DetailPresenter extends BasePresenter {

    public function renderDefault($aid) {
        $this->template->funkce = $this->isAllowed("EV_EventFunction_ALL_EventCamp") ? $this->context->campService->event->getFunctions($aid) : false;
        $this->template->accessDetail = $this->isAllowed(self::STable . "_DETAIL");
        $this->template->skautISUrl = $this->context->skautis->getHttpPrefix() . ".skaut.cz/";

        if (is_array($this->event->ID_UnitArray->string)) {
            $this->template->troops = array_map(function($id) {
                return $this->context->unitService->getDetail($id);
            }, $this->event->ID_UnitArray->string);
        } elseif (is_string($this->event->ID_UnitArray->string)) {
            $this->template->troops = array($this->context->unitService->getDetail($this->event->ID_UnitArray->string));
        }
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }

        $form = $this['formEdit'];
        $form->setDefaults(array(
            "aid" => $aid,
            "prefix" => $this->event->prefix,
        ));
    }

    public function renderReport($aid) {
        if (!$this->isAllowed("EV_EventFunction_ALL_EventCamp")) {
            $this->flashMessage("Nemáte právo přistupovat k táboru", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        if (!$this->context->campService->chits->isConsistent($aid)) {
            $this->flashMessage("Data v účtech a ve skautisu jsou nekonzistentní!", "warning");
            $this->redirect("default", array("aid" => $aid));
        }

        $template = $this->context->exportService->getCampReport($this->createTemplate(), $aid, $this->context->campService);
        $this->context->campService->participants->makePdf($template, "reportCamp.pdf");
        $this->terminate();
    }

    function createComponentFormEdit($name) {
        $form = $this->prepareForm($this, $name);
        $form->addProtection();
        $form->addText("prefix", "Prefix", NULL, 6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditSubmitted(Form $form) {
        if (!$this->isAllowed("EV_EventCamp_DETAIL")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu tábora", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        if ($this->context->campService->event->updatePrefix($values['aid'], $values['prefix'])) {
            $this->flashMessage("Prefix byl nastaven.");
            //$this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se nastavit prefix.", "danger");
        }
        $this->redirect("this");
    }

    function createComponentFormAddFunction($name) {
        $combo = $this->context->memberService->getCombobox(FALSE, 18);

        $form = $this->prepareForm($this, $name);
        $form->addSelect("person", NULL, $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setAttribute("class", "combobox");
        $form->addHidden("fid");
        $form->addHidden("aid");
        $form->addSubmit('send', 'Přidat')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

}
