<?php

/**
 * @author Hána František
 * akce
 */
class Accountancy_Camp_DetailPresenter extends Accountancy_Camp_BasePresenter  {
    
    public function renderDefault($aid) {
        //nastavení dat do formuláře pro editaci
        $func = false;
        if (array_key_exists("EV_EventFunction_ALL_EventCamp", $this->availableActions))
            $func = $this->context->campService->event->getFunctions($aid);
        
        $this->template->funkce = $func;
        $this->template->accessDetail = array_key_exists(self::STable . "_DETAIL", $this->availableActions);
        $this->template->skautISUrl = $this->context->skautIS->getHttpPrefix() . ".skaut.cz/";
    }
}

