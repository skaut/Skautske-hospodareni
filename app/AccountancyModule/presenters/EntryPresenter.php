<?php

/**
 * @author sinacek
 * nastavuje základní údaje o akci
 */
//class Accountancy_EntryPresenter extends Accountancy_BasePresenter {
//    
//    function startup() {
//        parent::startup();
//        
//    }
//    
//
//    // <editor-fold defaultstate="collapsed" desc="desc">
//    // </editor-fold>
//    function createComponentFormEntry($name) {
//
//        $form = new AppForm($this, $name);
//        $form->addText("name", "Název akce")
//                ;//->addRule(Form::FILLED, "Zadej název akce");
//        $form->addText("leaderName", "leader name");
//        $form->addSelect("leaderSelect", "Vyberte ze seznamu", array());
//        $form->addText("executorName", "executorName");
//        $form->addSelect("executorSelect", "Vyberte ze seznamu", array());
//        $form->addDatePicker("start", "Začátek");
//        $form->addDatePicker("end", "Konec");
//        $form->addText("place", "Místo konání");
//        
//        $form->addSubmit('send', 'Pokračovat');
//        $form->onSuccess[] = array($this, $name . 'Submitted');
//        return $form;
//    }
//
//    function formEntrySubmitted(AppForm $form) {
//        $values = $form->getValues();
//
//        throw new NotImplementedException();
//        //dump($values);
//        //$this->flashMessage("Základní údaje byly úspěšně nastaveny");
//        $this->redirect(":Accountancy:Default:action");
//    }
//
//
//}
