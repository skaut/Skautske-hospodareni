<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

/**
 * @author Hána František
 * akce
 */
class DetailPresenter extends BasePresenter {

    public function renderDefault($aid) {
        $this->template->funkce = $this->isAllowed("EV_EventFunction_ALL_EventCamp") ? $this->context->campService->event->getFunctions($aid) : false;
        $this->template->accessDetail = $this->isAllowed(self::STable . "_DETAIL");
        $this->template->skautISUrl = $this->context->skautIS->getHttpPrefix() . ".skaut.cz/";
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

        $template = $this->context->exportService->getCampReport($aid, $this->context->campService);
        $this->context->campService->participants->makePdf($template, "reportCamp.pdf");
        $this->terminate();
    }

    function createComponentFormEdit($name) {
        $form = new Form($this, $name);
        $form->addProtection();
        $form->addText("prefix", "Prefix", NULL, 6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
                        ->setAttribute("class", "btn btn-primary")
                ->onClick[] = $this->{$name . "Submitted"};
        return $form;
    }

    function formEditSubmitted(SubmitButton $button) {
        if (!$this->isAllowed("EV_EventCamp_DETAIL")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu tábora", "danger");
            $this->redirect("this");
        }
        $values = $button->getForm()->getValues();

        if ($this->context->campService->event->updatePrefix($values['aid'], $values['prefix'])) {
            $this->flashMessage("Prefix byl nastaven.");
            //$this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se nastavit prefix.", "danger");
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

}
