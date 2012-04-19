<?php

/**
 * @author sinacek
 */
class Accountancy_CashbookPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "error");
            $this->redirect("Event:");
        }
    }

    function renderDefault($aid) {
        $this->template->isInMinus = $this->context->chitService->isInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->isEditable = $this->context->eventService->isEditable($this->aid);
        $this->template->autoCompleter = $this->context->userService->getAC();
        $this->template->list = $this->context->chitService->getAll($aid);
    }

    function renderEdit($id, $aid) {
        $defaults = $this->context->chitService->get($id);
        $defaults['id'] = $id;
        $defaults['price'] = $defaults['priceText'];
        $defaults['type'] = $defaults['category'];

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
        $this->template->autoCompleter = $this->context->userService->getAC();
    }

    function handleRemove($id, $actionId) {
        if ($this->context->chitService->delete($id, $actionId)) {
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

    //FORM OUT
    function createComponentFormOutAdd($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addSubmit('send', 'Uložit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, 'formAddSubmitted');
        $form->setDefaults(array('type' => 'un'));
        return $form;
    }

    /**
     * formular na úpravu výdajových dokladů
     * @param string $name
     * @return AppForm 
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
     * generuje základní AppForm pro ostatní formuláře
     * @param Presenter $thisP
     * @param <type> $name
     * @return AppForm
     */
    protected static function makeFormOUT($thisP, $name) {
        $form = new AppForm($thisP, $name);
        $form->addDatePicker("date", "Ze dne:", 15)
                ->addRule(Form::FILLED, 'Zadejte datum');
        //@TODO kontrola platneho data, problem s componentou
        $form->addText("recipient", "Vyplaceno komu:", 20, 30)
                ->setHtmlId("form-out-recipient");
        $form->addText("purpose", "Účel výplaty:", 20, 50)
                ->addRule(Form::FILLED, 'Zadejte účel výplaty')
                ->getControlPrototype()->placeholder("3 první položky");
        $form->addText("price", "Cena celkem: ", 20, 100)
                ->setHtmlId("form-out-price")
//                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
                ->getControlPrototype()->placeholder("vzorce např.20+15*3");
        $categories = $thisP->context->chitService->getCategoriesOut();
        $form->addRadioList("type", "Typ: ", $categories)
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        return $form;
    }

    //FORM IN    
    function createComponentFormInAdd($name) {
        $form = $this->makeFormIn($this, $name);
        $form->addSubmit('send', 'Uložit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, 'formAddSubmitted');
        $form->setDefaults(array('type' => 'pp'));
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
        $form = new AppForm($thisP, $name);
        $form->addDatePicker("date", "Ze dne:", 15)
                ->addRule(Form::FILLED, 'Zadejte datum');
        $form->addText("recipient", "Přijato od:", 20, 30)
                ->setHtmlId("form-in-recipient");
        $form->addText("purpose", "Účel příjmu:", 20, 50)
                ->addRule(Form::FILLED, 'Zadejte účel přijmu');
        $form->addText("price", "Částka: ", 20, 100)
                ->setHtmlId("form-in-price")
                //->addRule(Form::REGEXP, 'Zadejte platnou částku', "/^([0-9]+(.[0-9]{0,2})?[\+\*])*[0-9]+([.][0-9]{0,2})?$/")
                ->getControlPrototype()->placeholder("vzorce 20+15*3");
        $categories = $thisP->context->chitService->getCategoriesIn();
        $form->addRadioList("type", "Typ: ", $categories)
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        return $form;
    }

    /**
     * přidává paragony všech kategorií
     * @param AppForm $form 
     */
    function formAddSubmitted(AppForm $form) {
        $values = $form->getValues();
        
        $values['priceText'] = $values['price'];
        $values['price'] = $this->solveString($values['price']);

        try {
            $add = $this->context->chitService->add($this->aid, $values);
            $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
            if ($this->context->chitService->isInMinus($this->aid))
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

    function formEditSubmitted(AppForm $form) {
        $values = $form->getValues();
        $id = $values['id'];
        unset($values['id']);
        $values['priceText'] = $values['price'];
        $values['price'] = $this->solveString($values['price']);

        if ($this->context->chitService->update($id, $values)) {
            $this->flashMessage("Paragon byl upraven.");
        } else {
            $this->flashMessage("Paragon se nepodařilo upravit.", "danger");
        }

        if ($this->context->chitService->isInMinus($this->aid))
            $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
        $this->redirect("default", array("aid" => $this->aid));
    }

    // <editor-fold defaultstate="collapsed" desc="solveString">
    /**
     * vyhodnotí řetězec obsahující čísla, +, *
     * @param string $str - výraz k výpčtu
     * @return int 
     */
    function solveString($str) {
        $str = str_replace(",", ".", $str); //prevede desetinou carku na tecku
        preg_match_all('/(?P<cislo>[0-9]+[.]?[0-9]{0,2})(?P<operace>[\+\*]+)?/', $str, $matches);
        $maxIndex = count($matches['cislo']);
        foreach ($matches['operace'] as $index => $op) { //vyřeší operaci násobení
            if ($op == "*" && $index + 1 <= $maxIndex) {
                $matches['cislo'][$index + 1] = $matches['cislo'][$index] * $matches['cislo'][$index + 1];
                $matches['cislo'][$index] = 0;
            }
        }
        
        return array_sum($matches['cislo']);
    }

// </editor-fold>
}

