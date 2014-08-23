<?php

namespace App\AccountancyModule\EventModule;

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "error");
            $this->redirect("Event:");
        }

        $ev_state = $this->event->ID_EventGeneralState == "draft" ? TRUE : FALSE;
        $this->isEditable = $this->template->isEditable = $ev_state && array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->template->missingCategories = FALSE;
    }

    function renderDefault($aid) {
        $this->template->isInMinus = $this->context->eventService->chits->isInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
//        $this->template->isEditable = $this->context->eventService->event->isCommandEditable($this->aid);
        $this->template->autoCompleter = $this->context->memberService->getAC();
        $this->template->list = $this->context->eventService->chits->getAll($aid);
        $this->template->linkImportHPD = $this->link("importHpd", array("aid"=>$aid));
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    function renderEdit($id, $aid) {
        $this->editableOnly();
        $defaults = $this->context->eventService->chits->get($id);
        $defaults['id'] = $id;
        $defaults['price'] = $defaults['priceText'];

        if ($defaults['ctype'] == "out") {
            $form = $this['formOutEdit'];
            $form->setDefaults($defaults);
            $this->template->ctype = $defaults['ctype'];
        } else {
            $form = $this['formInEdit'];
            $form->setDefaults($defaults);
        }
        $form['recipient']->setHtmlId("form-edit-recipient");
        $form['price']->setHtmlId("form-edit-price");
        $this->template->form = $form;
        $this->template->autoCompleter = $this->context->memberService->getAC();
    }

    //AJAX edit
    public function actionEditField($aid, $id, $field, $value) {
        $this->editableOnly();

        if ($field == "price") {
            $this->context->eventService->chits->update($id, array("price" => $value));
        }

        $this->terminate();
    }

    public function actionImportHpd($aid) {
        $this->editableOnly();
        $totalPayment = $this->context->eventService->participants->getTotalPayment($this->aid);
        $func = $this->context->eventService->event->getFunctions($this->aid);
        $hospodar = ($func[2]->ID_Person != null) ? $func[2]->Person : ""; //$func[0]->Person
        $date = $this->context->eventService->event->get($aid)->StartDate;
        $category = $this->context->eventService->chits->getEventCategoryParticipant();

        $values = array("date" => $date, "recipient" => $hospodar, "purpose" => "účastnické příspěvky", "price" => $totalPayment, "category" => $category);
        $add = $this->context->eventService->chits->add($this->aid, $values);
        if ($add) {
            $this->flashMessage("Účastníci byli importováni");
        } else {
            $this->flashMessage("Účastníky se nepodařilo importovat", "fail");
        }
        $this->redirect("default", array("aid" => $aid));
    }

    public function actionExport($aid) {
        $template = $this->context->exportService->getCashbook($aid, $this->context->eventService);
        $this->context->eventService->chits->makePdf($template, "pokladni-kniha.pdf");
        $this->terminate();
    }

    public function actionExportExcel($aid) {
        $this->context->excelService->getCashbook($this->context->eventService, $this->event);
        $this->terminate();
    }

    function actionPrint($id, $aid) {
        $chits = array($this->context->eventService->chits->get($id));
        $template = $this->context->exportService->getChits($aid, $this->context->eventService, $this->context->unitService, $chits);
//        echo $template->render();
        $this->context->eventService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    function handleRemove($id, $actionId) {
        $this->editableOnly();
        if ($this->context->eventService->chits->delete($id, $actionId)) {
            $this->flashMessage("Paragon byl smazán");
        } else {
            $this->flashMessage("Paragon se nepodařilo smazat");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect('this', $actionId);
        }
    }

    function createComponentFormMass($name) {
        $form = new Form($this, $name);
        $form->addSubmit('massPrintSend')
                ->onClick[] = $this->massPrintSubmitted;
        return $form;
    }

    function massPrintSubmitted(SubmitButton $button) {
        $chits = $this->context->eventService->chits->getIn($this->aid, $button->getForm()->getHttpData(Form::DATA_TEXT, 'chits[]'));
        $template = $this->context->exportService->getChits($this->aid, $this->context->eventService, $this->context->unitService, $chits, "camp");
        $this->context->eventService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    //FORM OUT
    function createComponentFormOutAdd($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addSubmit('send', 'Uložit')
                        ->setAttribute("class", "btn btn-primary")
                ->onClick[] = array($this, 'formAddSubmitted');
        //$form->setDefaults(array('category' => 'un'));
        return $form;
    }

    /**
     * formular na úpravu výdajových dokladů
     * @param string $name
     * @return Form 
     */
    function createComponentFormOutEdit($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addHidden('id');
        $form->addSubmit('send', 'Uložit')
                        ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, 'formEditSubmitted');
        return $form;
    }

    /**
     * generuje základní Form pro ostatní formuláře
     * @param Presenter $thisP
     * @param <type> $name
     * @return Form
     */
    protected static function makeFormOUT($thisP, $name) {
        $form = new Form($thisP, $name);
        $form->addDatePicker("date", "Ze dne:", 15)
                ->addRule(Form::FILLED, 'Zadejte datum')
                ->getControlPrototype()->class("input-medium");
        //@TODO kontrola platneho data, problem s componentou
        $form->addText("recipient", "Vyplaceno komu:", 20, 50)
                ->setHtmlId("form-out-recipient")
                ->getControlPrototype()->class("input-medium");
        $form->addText("purpose", "Účel výplaty:", 20, 40)
                ->addRule(Form::FILLED, 'Zadejte účel výplaty')
                ->getControlPrototype()->placeholder("3 první položky")
                ->class("input-medium");
        $form->addText("price", "Částka: ", 20, 100)
                ->setHtmlId("form-out-price")
//                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
                ->getControlPrototype()->placeholder("např. 20+15*3")
                ->class("input-medium");
        $categories = $thisP->context->eventService->chits->getCategoriesOut();
        $form->addRadioList("category", "Typ: ", $categories)
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        if (isset($thisP->event->prefix) && $thisP->event->prefix != "") {
            $form->addText("num", "Číslo d.:", NULL, 5)
                    ->setAttribute('class', 'input-mini')
            ; //->addCondition(Form::FILLED)
            //    ->addRule(Form::INTEGER, "Číslo dokladu musí být číslo!");
        }
        return $form;
    }

    //FORM IN    
    function createComponentFormInAdd($name) {
        $form = $this->makeFormIn($this, $name);
        $form->addSubmit('send', 'Uložit')
                        ->setAttribute("class", "btn btn-primary")
                ->onClick[] = array($this, 'formAddSubmitted');
        //$form->setDefaults(array('category' => 'pp'));
        return $form;
    }

    function createComponentFormInEdit($name) {
        $form = self::makeFormIn($this, $name);
        $form->addHidden('id');
        $form->addSubmit('send', 'Uložit')
                        ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, 'formEditSubmitted');
        return $form;
    }

    protected static function makeFormIn($thisP, $name) {
        $form = new Form($thisP, $name);
        $form->addDatePicker("date", "Ze dne:", 15)
                ->addRule(Form::FILLED, 'Zadejte datum')
                ->getControlPrototype()->class("input-medium");
        $form->addText("recipient", "Přijato od:", 20, 30)
                ->setHtmlId("form-in-recipient")
                ->getControlPrototype()->class("input-medium");
        $form->addText("purpose", "Účel příjmu:", 20, 40)
                ->addRule(Form::FILLED, 'Zadejte účel přijmu')
                ->getControlPrototype()->class("input-medium");
        $form->addText("price", "Částka: ", 20, 100)
                ->setHtmlId("form-in-price")
                //->addRule(Form::REGEXP, 'Zadejte platnou částku', "/^([0-9]+(.[0-9]{0,2})?[\+\*])*[0-9]+([.][0-9]{0,2})?$/")
                ->getControlPrototype()->placeholder("např. 20+15*3")
                ->class("input-medium");
        $categories = $thisP->context->eventService->chits->getCategoriesIn();
        $form->addRadioList("category", "Typ: ", $categories)
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        if ($thisP->event->prefix != "") {
            $form->addText("num", "Číslo d.:", NULL, 5)
                    ->setAttribute('class', 'input-mini')
            ; //->addCondition(Form::FILLED)
            //    ->addRule(Form::INTEGER, "Číslo dokladu musí být číslo!");
        }
        return $form;
    }

    /**
     * přidává paragony všech kategorií
     * @param SubmitButton $button 
     */
    function formAddSubmitted(SubmitButton $button) {
        $this->editableOnly();
        $values = $button->getForm()->getValues();

        try {
            $this->context->eventService->chits->add($this->aid, $values);
            $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
            if ($this->context->eventService->chits->isInMinus($this->aid)) {
                $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
            }
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage("Paragon se nepodařilo přidat do seznamu.", "danger");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("tabs");
            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect("this");
        }
    }

    function formEditSubmitted(Form $form) {
        $this->editableOnly();
        $values = $form->getValues();
        $id = $values['id'];
        unset($values['id']);

        if ($this->context->eventService->chits->update($id, $values)) {
            $this->flashMessage("Paragon byl upraven.");
        } else {
            $this->flashMessage("Paragon se nepodařilo upravit.", "danger");
        }

        if ($this->context->eventService->chits->isInMinus($this->aid))
            $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
        $this->redirect("default", array("aid" => $this->aid));
    }

}
