<?php

/**
 * @author sinacek
 * stará se o nastavení dané jednotky
 */
class Accountancy_UnitPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        $this->service = new SkautIS_Mapper();
        
        //dump($this->service->getUnitId());
    }

    public function renderSelect() {
        $ses = $this->session->getSection(__CLASS__);
        $this->template->units = $ses->units;
    }
    
    public function renderCreate() {
        
    }
    
    public function createComponentSelectUnitForm($name) {
        $form = new AppForm($this, $name);
        $form->addText("evnum", "Ev. číslo" )
                ->addRule(Form::FILLED, "Zadejte ev. číslo");
        $form->addCheckbox("startWith", "Hledat podle začátku ev. čísla?");
        $form->addSubmit("ok", "Hledat");
        $form->onSuccess[] = array($this, $name . 'Submitted');
    }

    function selectUnitFormSubmitted(AppForm $form) {
        $values = $form->values;
        $ses = $this->session->getSection(__CLASS__);
        $ses->units = $this->service->callFunction("org", "UnitAll", array(
                "RegistrationNumber" => $values['evnum'],
                "RegistrationNumberStartWith" => $values['startWith']));
        $this->redirect("this");
    }

    public function createComponentCreateUnitForm($name) {
        $form = new AppForm($this, $name);

        $form->addSubmit("ok", "Poslat");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        
    }

    function createUnitFormSubmitted(AppForm $form) {
        $values = $form->values;
        dump($values);
        die();
    }

//
//    /**
//     * seznam akcí které můžu upravovat a prohlížet
//     */
//    function renderSavedActions() {
//        $this->template->list = $this->service->getAkceByUser($this->user->getIdentity()->data['id']);
//    }
//
//    /**
//     * smaže akci pokud na to mám práva
//     * @param int $id
//     */
//    function handleDelete($id) {
//
//        if ($this->service->delete($id)) {
//            $this->flashMessage("Akce byla smazána.");
//        } else {
//            $this->flashMessage("Akci se nepodařilo smazat.", "fail");
//        }
//
//        if ($this->isAjax()) {
//            $this->invalidateControl("savedActions");
//            $this->invalidateControl("flashmesages");
//        } else {
//            $this->redirect('savedActions');
//        }
//    }
}

