<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

/**
 * @author sinacek
 */
class CashbookPresenter extends BasePresenter {

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "error");
            $this->redirect("Default:");
        }
//        $this->template->isEditable = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
//        if ($this->camp->prefix == "") {
//            
//        }
//            unset($this['formOutEdit']['num']);
    }

    public function beforeRender() {
        parent::beforeRender();
        $this['formImportHpd']['aid']->setValue($this->aid);
    }

    function renderDefault($aid) {
        $this->template->isInMinus = $this->context->campService->chits->isInMinus($this->aid);
        $this->template->autoCompleter = $this->context->memberService->getAC();
        $this->template->list = $this->context->campService->chits->getAll($aid);
//        $this->template->missingCategories = false;
//        dump($this->camp);
        if (!$this->event->IsRealTotalCostAutoComputed && $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost")) { //nabízí možnost aktivovat dopočítávání, pokud již není aktivní a je dostupná
            $this->template->missingCategories = true; //boolean - nastavuje upozornění na chybějící dopočítávání kategorií
            $this->template->skautISHttpPrefix = $this->context->skautIS->getHttpPrefix();
        }
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    function renderEdit($id, $aid) {
        $this->editableOnly();
        $defaults = $this->context->campService->chits->get($id);
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

//    public function actionImportHpd($aid) {
//        $this->editableOnly();
//        
//        $totalPayment = $this->context->campService->participants->getTotalPayment($this->aid, "camp");
//        $func = $this->context->campService->event->getFunctions($this->aid);
//        $hospodar = ($func[2]->ID_Person != null) ? $func[2]->Person : $func[0]->Person;
//        $date = $this->context->campService->event->get($aid)->StartDate;
//        $category = $this->context->campService->chits->getCampCategoryParticipant($aid);
//
//        $values = array("date" => $date, "recipient" => $hospodar, "purpose" => "účastnické příspěvky", "price" => $totalPayment, "category" => $category);
//        $add = $this->context->campService->chits->add($this->aid, $values);
//        if ($add) {
//            $this->flashMessage("HPD byl importován");
//        } else {
//            $this->flashMessage("HPD se nepodařilo importovat", "fail");
//        }
//        $this->redirect("default", array("aid" => $aid));
//    }

    public function actionExport($aid) {
        $template = $this->context->exportService->getCashbook($aid, $this->context->campService);
        $this->context->campService->chits->makePdf($template, "pokladni-kniha.pdf");
        $this->terminate();
    }
    
    public function actionExportExcel($aid) {
        $this->context->excelService->getCashbook($this->context->campService, $this->event);
        $this->terminate();
    }

    function actionPrint($id, $aid) {
        $chits = array($this->context->campService->chits->get($id));
        $template = $this->context->exportService->getChits($aid, $this->context->campService, $this->context->unitService, $chits);
        $this->context->campService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    function handleRemove($id, $aid) {
        $this->editableOnly();

        try {
            if ($this->context->campService->chits->delete($id, $aid)) {
                $this->flashMessage("Paragon byl smazán");
            } else {
                $this->flashMessage("Paragon se nepodařilo smazat");
            }
        } catch (\SkautIS\Exception\WsdlException $exc) {
            $this->flashMessage("Nepodařilo se upravit záznamy ve skautisu.", "danger");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect('this', $aid);
        }
    }

    public function handleActivateAutocomputedCashbook($aid) {
        $this->context->campService->event->activateAutocomputedCashbook($aid);
        $this->flashMessage("Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.");
        $this->redirect("this");
    }

    function createComponentFormMass($name) {
        $form = new Form($this, $name);
        $form->addSubmit('massPrintSend')
                ->onClick[] = $this->massPrintSubmitted;
        return $form;
    }

    function massPrintSubmitted(SubmitButton $button) {
        $chits = $this->context->campService->chits->getIn($this->aid, $button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]'));
        $template = $this->context->exportService->getChits($this->aid, $this->context->campService, $this->context->unitService, $chits, "camp");
        $this->context->campService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    /**
     * formulář na výběr příjmů k importu
     * @param type $name
     * @return \Nette\Application\UI\Form
     */
    function createComponentFormImportHpd($name) {
        $form = new Form($this, $name);
        $form->addRadioList("cat", "Kategorie:", array("child" => "Od dětí a roverů", "adult" => "Od dospělých"))
                ->addRule(Form::FILLED, "Musíte vyplnit kategorii.")
                ->setDefaultValue("child");
        $form->addRadioList("isAccount", "Placeno:", array("N" => "Hotově", "Y" => "Přes účet"))
                ->addRule(Form::FILLED, "Musíte vyplnit způsob platby.")
                ->setDefaultValue("N");
        $form->addHidden("aid");

        $form->addSubmit('send', 'Importovat')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form['send']->onClick[] = callback($this, $name . 'Submitted');
        $form->setDefaults(array('category' => 'un'));
        return $form;
    }

    function formImportHpdSubmitted(SubmitButton $btn) {
        $this->editableOnly();

        $values = $btn->getForm()->getValues();
        //$func = $this->context->campService->event->getFunctions($values->aid);

        $data = array("date" => $this->context->campService->event->get($values->aid)->StartDate,
//            "recipient" => $func[2]->Person,
            "recipient" => "",
            "purpose" => "úč. příspěvky " . ($values->isAccount == "Y" ? "- účet" : "- hotovost"),
            "price" => $this->context->campService->participants->getCampTotalPayment($values->aid, $values->cat, $values->isAccount),
            "category" => $this->context->campService->chits->getCampCategoryParticipant($values->aid, $values->cat));

        if ($this->context->campService->chits->add($values->aid, $data)) {
            $this->flashMessage("HPD byl importován");
        } else {
            $this->flashMessage("HPD se nepodařilo importovat", "fail");
        }
        $this->redirect("default", array("aid" => $values->aid));
    }

//FORM OUT
    function createComponentFormOutAdd($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addSubmit('send', 'Uložit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, 'formAddSubmitted');
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
                ->setAttribute("class", "btn btn-primary")
                ->onClick[] = $this->formEditSubmitted;
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
        $form->addText("recipient", "Vyplaceno komu:", 20, 30)
                ->setHtmlId("form-out-recipient")
                ->getControlPrototype()->class("input-medium");
        $form->addText("purpose", "Účel výplaty:", 20, 40)
                ->addRule(Form::FILLED, 'Zadejte účel výplaty')
                ->getControlPrototype()->placeholder("3 první položky")->class("input-medium");
        $form->addText("price", "Částka: ", 20, 100)
                ->setHtmlId("form-out-price")
//                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
                ->getControlPrototype()->placeholder("např. 20+15*3")
                ->class("input-medium");
        $categories = $thisP->context->campService->chits->getCategoriesCampPairs($thisP->aid);
        $form->addRadioList("category", "Typ: ", $categories['out'])
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        if ($thisP->event->prefix != "") {
            $form->addText("num", "Číslo d.:", NULL, 5)
                    ->setAttribute('class', 'input-mini')
                    ;//->addCondition(Form::FILLED)
                        //->addRule(Form::INTEGER, "Číslo dokladu musí být číslo!");
        }
        return $form;
    }

//FORM IN    
    function createComponentFormInAdd($name) {
        $form = $this->makeFormIn($this, $name);
        $form->addSubmit('send', 'Uložit')
                ->setAttribute("class", "btn btn-primary")
                ->onClick[] = $this->formAddSubmitted;
        //$form->setDefaults(array('category' => 'pp'));
        return $form;
    }

    function createComponentFormInEdit($name) {
        $form = self::makeFormIn($this, $name);
        $form->addHidden('id');
        $form->addSubmit('send', 'Uložit')
                ->getControlPrototype()->setClass("btn btn-primary");
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
        $categories = $thisP->context->campService->chits->getCategoriesCampPairs($thisP->aid);
        $form->addRadioList("category", "Typ: ", $categories['in'])
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        if ($thisP->event->prefix != "") {
            $form->addText("num", "Číslo d.:", NULL, 5)
                    ->setAttribute('class', 'input-mini')
                    ;//->addCondition(Form::FILLED)
                     //   ->addRule(Form::INTEGER, "Číslo dokladu musí být číslo!");
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
            $this->context->campService->chits->add($this->aid, $values);
            $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
            if ($this->context->campService->chits->isInMinus($this->aid)) {
                $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
            }
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage("Paragon se nepodařilo přidat do seznamu.", "danger");
        } catch (\SkautIS\Exception\WsdlException $se) {
            $this->flashMessage("Nepodařilo se upravit záznamy ve skautisu.", "danger");
//            $this->flashMessage("Nepodařilo se synchronizovat kategorie.");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("tabs");
            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect("this");
        }
    }

    function formEditSubmitted(SubmitButton $button) {
        $this->editableOnly();
        $values = $button->getForm()->getValues();
        $chitId = $values['id'];
        unset($values['id']);

        try {
            if ($this->context->campService->chits->update($chitId, $values)) {
                $this->flashMessage("Paragon byl upraven.");
            } else {
                $this->flashMessage("Paragon se nepodařilo upravit.", "danger");
            }
        } catch (\SkautIS\Exception\WsdlException $exc) {
            $this->flashMessage("Nepodařilo se upravit záznamy ve skautisu.", "danger");
        }


        if ($this->context->campService->chits->isInMinus($this->aid)) {
            $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
        }
        $this->redirect("default", array("aid" => $this->aid));
    }

}

