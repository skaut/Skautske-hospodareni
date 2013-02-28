<?php

namespace AccountancyModule\CampModule;

use Nette\Application\UI\Form,
    Nette\Utils\Strings;

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
        $this->template->isEditable = array_key_exists("EV_EventCamp_UPDATE_RealTotalCost", $this->availableActions);
    }

    function renderDefault($aid) {
//        $this->template->isInMinus = $this->context->campService->chits->isInMinus($this->aid);
        $this->template->autoCompleter = $this->context->memberService->getAC();
        $this->template->list = $this->context->campService->chits->getAll($aid);
        if (!$this->camp->IsRealTotalCostAutoComputed) {
            $this->template->missingCategories = true;
            $this->template->skautISHttpPrefix = $this->context->skautIS->getHttpPrefix();
            $this->template->isEditable = false; //zamezení zobrazení formulářů, protoze nemají kategorie
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

    public function actionImportHpd($aid) {
        $this->editableOnly();
        $totalPayment = $this->context->campService->participants->getTotalPayment($this->aid);
        $func = $this->context->campService->event->getFunctions($this->aid);
        $hospodar = ($func[2]->ID_Person != null) ? $func[2]->Person : $func[0]->Person;
        $date = $this->context->campService->event->get($aid)->StartDate;
        $category = $this->context->campService->chits->getCampCategoryParticipant($aid);

        $values = array("date" => $date, "recipient" => $hospodar, "purpose" => "účastnické příspěvky", "price" => $totalPayment, "category" => $category);
        $add = $this->context->campService->chits->add($this->aid, $values);
        if ($add) {
            $this->flashMessage("HPD byl importován");
        } else {
            $this->flashMessage("HPD se nepodařilo importovat", "fail");
        }
        $this->redirect("default", array("aid" => $aid));
    }

    public function actionExport($aid) {
        $chits = $this->context->campService->chits->getAll($aid);
        $actionInfo = $this->context->campService->event->get($this->aid);
        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Cashbook/export.latte');
        $template->registerHelper('price', 'AccountancyHelpers::price');
        $template->list = $chits;
        $template->info = $actionInfo;
        $this->context->campService->chits->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_pokladni-kniha.pdf");
        $this->terminate();
    }

    function actionPrint($id, $aid) {
        $chits = array($this->context->campService->chits->get($id));
        $template = $this->context->exportService->getChits($aid, $this->context->campService, $this->context->unitService, $chits);
        $this->context->eventService->chits->makePdf($template, "paragony.pdf");
        $this->terminate();
    }

    function handleRemove($id, $aid) {
        $this->editableOnly();

        if ($this->context->campService->chits->delete($id, $aid)) {
            $this->flashMessage("Paragon byl smazán");
        } else {
            $this->flashMessage("Paragon se nepodařilo smazat");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect('this', $aid);
        }
    }

    function createComponentFormMass($name) {
        $form = new Form($this, $name);
        $chits = $this->context->campService->chits->getAll($this->aid);

        $group = $form->addContainer('chits');
        foreach ($chits as $c) {
            $group->addCheckbox($c->id);
        }

        $form->addSubmit('printSend', 'Vytisknout vybrané')
                ->getControlPrototype()->setClass("btn btn-info btn-small");
        $form['printSend']->onClick[] = callback($this, 'massPrintSubmitted');
        $form->setDefaults(array('category' => 'un'));
        return $form;
    }

    function massPrintSubmitted(\Nette\Forms\Controls\SubmitButton $btn) {
        $values = $btn->getForm()->getValues();
        $selected = array();
        foreach ($values['chits'] as $id => $bool) {
            if ($bool)
                $selected[] = $id;
        }
        $chits = $this->context->campService->chits->getIn($this->aid, $selected);

        $actionInfo = $this->context->campService->event->get($this->aid);
        $this->context->campService->chits->printChits($this->context->unitService, $this->template, $actionInfo, $chits, "paragony_" . Strings::webalize($actionInfo->Event));
    }

    //FORM OUT
    function createComponentFormOutAdd($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addSubmit('send', 'Uložit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, 'formAddSubmitted');
        $form->setDefaults(array('category' => 'un'));
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
                ->getControlPrototype()->setClass("btn btn-primary");
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
//                ->setAttribute('autofocus')
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
        return $form;
    }

    //FORM IN    
    function createComponentFormInAdd($name) {
        $form = $this->makeFormIn($this, $name);
        $form->addSubmit('send', 'Uložit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, 'formAddSubmitted');
        $form->setDefaults(array('category' => 'pp'));
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
        return $form;
    }

    /**
     * přidává paragony všech kategorií
     * @param Form $form 
     */
    function formAddSubmitted(Form $form) {
        $this->editableOnly();
        $values = $form->getValues();

        try {
            $this->context->campService->chits->add($this->aid, $values);
            $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
            if ($this->context->campService->chits->isInMinus($this->aid))
                $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
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

        if ($this->context->campService->chits->update($id, $values)) {
            $this->flashMessage("Paragon byl upraven.");
        } else {
            $this->flashMessage("Paragon se nepodařilo upravit.", "danger");
        }

        if ($this->context->campService->chits->isInMinus($this->aid))
            $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
        $this->redirect("default", array("aid" => $this->aid));
    }

}

