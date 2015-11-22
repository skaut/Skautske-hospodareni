<?php

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

trait CashbookTrait {

    protected $entityService;

    function renderEdit($id, $aid) {
        $this->editableOnly();
        $this->isChitEditable($id, $this->entityService);

        $defaults = $this->entityService->chits->get($id);
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
        $this->template->autoCompleter = array_values($this->memberService->getCombobox(FALSE, 15));
    }

//    //AJAX edit
//    public function actionEditField($aid, $id, $field, $value) {
//        $this->editableOnly();
//        $this->isChitEditable($id, $this->entityService);
//
//        if ($field == "price") {
//            $this->entityService->chits->update($id, array("price" => $value));
//        }
//
//        $this->terminate();
//    }
//
//    public function actionImportHpd($aid) {
//        $this->editableOnly();
//        $totalPayment = $this->entityService->participants->getTotalPayment($this->aid);
//        $func = $this->entityService->event->getFunctions($this->aid);
//        $hospodar = ($func[2]->ID_Person != null) ? $func[2]->Person : ""; //$func[0]->Person
//        $date = $this->entityService->event->get($aid)->StartDate;
//        $category = $this->entityService->chits->getEventCategoryParticipant();
//
//        $values = array("date" => $date, "recipient" => $hospodar, "purpose" => "účastnické příspěvky", "price" => $totalPayment, "category" => $category);
//        $add = $this->entityService->chits->add($this->aid, $values);
//        if ($add) {
//            $this->flashMessage("Účastníci byli importováni");
//        } else {
//            $this->flashMessage("Účastníky se nepodařilo importovat", "fail");
//        }
//        $this->redirect("default", array("aid" => $aid));
//    }
//
    public function actionExport($aid) {
        $template = $this->exportService->getCashbook($this->createTemplate(), $aid, $this->entityService);
        $this->entityService->chits->makePdf($template, "pokladni-kniha.pdf");
        $this->terminate();
    }

    public function actionExportExcel($aid) {
        $this->excelService->getCashbook($this->entityService, $this->event);
        $this->terminate();
    }

    function actionPrint($id, $aid) {
        $chits = array($this->entityService->chits->get($id));
        $template = $this->exportService->getChits($this->createTemplate(), $aid, $this->entityService, $this->unitService, $chits);
//        echo $template->render();
        $this->entityService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    function handleRemove($id, $actionId) {
        $this->editableOnly();
        $this->isChitEditable($id, $this->entityService);

        if ($this->entityService->chits->delete($id, $actionId)) {
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
        $form = $this->prepareForm($this, $name);
        $form->addSubmit('massPrintSend')
                ->onClick[] = $this->massPrintSubmitted;
        $form->addSubmit('massExportSend')
                ->onClick[] = $this->massExportSubmitted;
        return $form;
    }

    function massPrintSubmitted(SubmitButton $button) {
        $chits = $this->entityService->chits->getIn($this->aid, $button->getForm()->getHttpData(Form::DATA_TEXT, 'chits[]'));
        $template = $this->exportService->getChits($this->createTemplate(), $this->aid, $this->entityService, $this->unitService, $chits);
        $this->entityService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    function massExportSubmitted(SubmitButton $button) {
        $chits = $this->entityService->chits->getIn($this->aid, $button->getForm()->getHttpData(Form::DATA_TEXT, 'chits[]'));
        $this->excelService->getChitsExport($chits);
        $this->terminate();
    }

    //FORM CASHBOOK
    public function getCategoriesByType($form, $dependentSelectBoxName) {
        return $this->entityService->chits->getCategoriesPairs($form["type"]->getValue(), $this->aid);
    }

    function createComponentCashbookForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addDatePicker("date", "Ze dne:")
                ->addRule(Form::FILLED, 'Zadejte datum')
                ->getControlPrototype()->class("form-control input-sm required")->placeholder("Datum");
        $form->addText("num", "Číslo d.:")
                ->setMaxLength(5)
                ->getControlPrototype()->placeholder("Číslo")
                ->class("form-control input-sm");
        $form->addText("purpose", "Účel výplaty:")
                ->setMaxLength(40)
                ->addRule(Form::FILLED, 'Zadejte účel výplaty')
                ->getControlPrototype()->placeholder("Účel")
                ->class("form-control input-sm required");
        $form->addSelect("type", "Typ", array("in" => "Příjmy", "out" => "Výdaje"))
                //->setAttribute("class", "form-control)
                ->setAttribute("size", "2")
                ->setDefaultValue("out")
                ->addRule(Form::FILLED, "Vyberte typ");
        $form->addJSelect("category", "Kategorie", $form["type"], array($this, "getCategoriesByType"))
                ->setAttribute("class", "form-control input-sm");
        $form->addText("recipient", "Vyplaceno komu:")
                ->setMaxLength(64)
                ->setHtmlId("form-recipient")
                ->getControlPrototype()->class("form-control input-sm")->placeholder("Komu/Od");
        $form->addText("price", "Částka: ")
                ->setMaxLength(100)
                ->setHtmlId("form-out-price")
//                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
                ->getControlPrototype()->placeholder("Částka: 2+3*15")
                ->class("form-control input-sm");
        $form->addHidden("pid");
        $form->addSubmit('send', 'Uložit')
                ->setAttribute("class", "btn btn-primary");
        //$form->setDefaults(array('category' => 'un'));
        $form->onSuccess[] = array($this, 'cashbookFormSubmitted');
        return $form;
    }

    /**
     * přidává paragony všech kategorií
     * @param Form $form 
     */
    function cashbookFormSubmitted(Form $form) {
        if ($form["send"]->isSubmittedBy()) {
            $this->editableOnly();
            $values = $form->getValues();

            try {
                if ($values['pid'] != "") {//EDIT
                    $chitId = $values['pid'];
                    unset($values['id']);
                    $this->isChitEditable($chitId, $this->entityService);
                    if ($this->entityService->chits->update($chitId, $values)) {
                        $this->flashMessage("Paragon byl upraven.");
                    } else {
                        $this->flashMessage("Paragon se nepodařilo upravit.", "danger");
                    }
                } else {//ADD
                    $this->entityService->chits->add($this->aid, $values);
                    $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
                }
                if ($this->entityService->chits->eventIsInMinus($this->aid)) {
                    $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
                }
            } catch (InvalidArgumentException $exc) {
                $this->flashMessage("Paragon se nepodařilo přidat do seznamu.", "danger");
            } catch (\Skautis\Wsdl\WsdlException $se) {
                $this->flashMessage("Nepodařilo se upravit záznamy ve skautisu.", "danger");
            }

//            if ($this->isAjax()) {
//                $this->invalidateControl("paragony");
//                $this->invalidateControl("flash");
//            } else {
//                $this->redirect("default", array("aid" => $this->aid));
//            }
            $this->redirect("default", array("aid" => $this->aid));
        }
    }

    /**
     * ověřuje editovatelnost paragonu a případně vrací chybovou hlášku rovnou
     * @param type $chitId
     * @param type $service
     */
    protected function isChitEditable($chitId, $service) {
        $chit = $service->chits->get($chitId);
        if ($chit !== FALSE && is_null($chit->lock)) {
            return TRUE;
        }
        $this->flashMessage("Paragon není možné upravovat!", "danger");
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect("this");
        }
    }

//FORM OUT
//    function createComponentFormOutAdd($name) {
//        $form = self::makeFormOUT($this, $name);
//        $form->addSubmit('send', 'Uložit')
//                ->setAttribute("class", "btn btn-primary");
//        $form->onSuccess[] = array($this, 'formAddSubmitted');
//        //$form->setDefaults(array('category' => 'un'));
//        return $form;
//    }
//
//    /**
//     * formular na úpravu výdajových dokladů
//     * @param string $name
//     * @return Form 
//     */
//    function createComponentFormOutEdit($name) {
//        $form = self::makeFormOUT($this, $name);
//        $form->addHidden('id');
//        $form->addSubmit('send', 'Uložit')
//                ->setAttribute("class", "btn btn-primary");
//        $form->onSuccess[] = array($this, 'formEditSubmitted');
//        return $form;
//    }
//
//    /**
//     * generuje základní Form pro ostatní formuláře
//     * @param Presenter $thisP
//     * @param <type> $name
//     * @return Form
//     */
//    protected static function makeFormOUT($thisP, $name) {
//        $form = new Form($thisP, $name);
//        $form->addDatePicker("date", "Ze dne:", 15)
//                ->addRule(Form::FILLED, 'Zadejte datum')
//                ->getControlPrototype()->class("form-control");
//        //@TODO kontrola platneho data, problem s componentou
//        $form->addText("recipient", "Vyplaceno komu:")
//                ->setHtmlId("form-out-recipient")
//                ->getControlPrototype()->class("form-control");
//        $form->addText("purpose", "Účel výplaty:")
//                ->setMaxLength(40)
//                ->addRule(Form::FILLED, 'Zadejte účel výplaty')
//                ->getControlPrototype()->placeholder("3 první položky")
//                ->class("form-control");
//        $form->addText("price", "Částka: ")
//                ->setMaxLength(100)
//                ->setHtmlId("form-out-price")
////                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
//                ->getControlPrototype()->placeholder("např. 20+15*3")
//                ->class("form-control");
//        $categories = $thisP->entityService->chits->getCategoriesPairs('out', $thisP->aid);
//        $form->addRadioList("category", "Typ: ", $categories)
//                ->addRule(Form::FILLED, 'Zadej typ paragonu');
//        if (isset($thisP->event) && isset($thisP->event->prefix) && $thisP->event->prefix != "") {
//            $form->addText("num", "Číslo d.:")
//                ->setMaxLength(5)            
//                    ->setAttribute('class', 'form-control input-sm');
//        }
//        return $form;
//    }
//
//    //FORM IN    
//    function createComponentFormInAdd($name) {
//        $form = $this->makeFormIn($this, $name);
//        $form->addSubmit('send', 'Uložit')
//                ->setAttribute("class", "btn btn-primary");
//        $form->onSuccess[] = array($this, 'formAddSubmitted');
//        return $form;
//    }
//
//    function createComponentFormInEdit($name) {
//        $form = self::makeFormIn($this, $name);
//        $form->addHidden('id');
//        $form->addSubmit('send', 'Uložit')
//                ->setAttribute("class", "btn btn-primary");
//        $form->onSuccess[] = array($this, 'formEditSubmitted');
//        return $form;
//    }
//
//    protected static function makeFormIn($thisP, $name) {
//        $form = new Form($thisP, $name);
//        $form->addDatePicker("date", "Ze dne:", 15)
//                ->addRule(Form::FILLED, 'Zadejte datum')
//                ->getControlPrototype()->class("form-control");
//        $form->addText("recipient", "Přijato od:", 20, 30)
//                ->setHtmlId("form-in-recipient")
//                ->getControlPrototype()->class("form-control");
//        $form->addText("purpose", "Účel příjmu:", 20, 40)
//                ->addRule(Form::FILLED, 'Zadejte účel přijmu')
//                ->getControlPrototype()->class("form-control");
//        $form->addText("price", "Částka: ", 20, 100)
//                ->setHtmlId("form-in-price")
//                //->addRule(Form::REGEXP, 'Zadejte platnou částku', "/^([0-9]+(.[0-9]{0,2})?[\+\*])*[0-9]+([.][0-9]{0,2})?$/")
//                ->getControlPrototype()->placeholder("např. 20+15*3")
//                ->class("form-control");
//        $categories = $thisP->entityService->chits->getCategoriesPairs('in', $thisP->aid);
//        $form->addRadioList("category", "Typ: ", $categories)
//                ->addRule(Form::FILLED, 'Zadej typ paragonu');
//        if (isset($thisP->event) && isset($thisP->event->prefix) && $thisP->event->prefix != "") {
//            $form->addText("num", "Číslo d.:", NULL, 5)
//                    ->setAttribute('class', 'form-control input-sm');
//        }
//        return $form;
//    }
}
