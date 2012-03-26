<?php

/**
 * @author sinacek
 * stará se o nastavení dané jednotky
 */
class Accountancy_UnitPresenter extends Accountancy_BasePresenter {

//    public function startup() {
//        parent::startup();
//        /**
//         * @var Accountancy_UnitService
//         */
//        $this->service = new UnitService();
//        
//        //dump($this->service->getUnitId());
//    }
//
//    public function renderSelect() {
//        $ses = $this->session->getSection(__CLASS__);
//        $ses->setExpiration("120");
//        $units = $ses->units;
//        
//        if(!isset ($units) || !is_array($units)){ //na zacatku nabizi vlastni jednotku
//            $units = array($this->service->getDetail());
//        }
//        $this->template->units = $units;
//    }
//    
//    public function createComponentSelectUnitForm($name) {
//        $form = new AppForm($this, $name);
//        $form->addText("evnum", "Ev. číslo" )
//                ->addRule(Form::FILLED, "Zadejte ev. číslo");
//        $form->addCheckbox("startWith", "Hledat podle začátku ev. čísla?");
//        $form->addSubmit("ok", "Hledat");
//        $form->onSuccess[] = array($this, $name . 'Submitted');
//    }
//
//    public function selectUnitFormSubmitted(AppForm $form) {
//        $values = $form->values;
//        $ses = $this->session->getSection(__CLASS__);
//        $ses->units = $this->service->org->UnitAll(array(
//                "RegistrationNumber" => $values['evnum'],
//                "RegistrationNumberStartWith" => $values['startWith']));
//        $this->redirect("this");
//    }
//    
//    public function handleCreate($id){
//        if($this->service->isCreated($id)){
//            $this->flashMessage("Jednotka již má aktivované účetnictví", "fail");
//            $this->redirect("this");
//        }
//        if($this->service->create($id)){
//            $this->flashMessage("Účetnictví bylo aktivováno.");
//        } 
//        
//        $this->redirect("this");
//    }
//



























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

