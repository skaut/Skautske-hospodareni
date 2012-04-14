<?php

/**
 * @author Hána František
 */
class SkautisService {

    protected $skautIS = NULL;

    public function __construct() {
        $this->skautIS = SkautIS::getInstance();
        $this->skautIS->nette = true;
    }

    public function __call($name, $arguments) {
        try {
            if (count($arguments) == 1)
                $arguments = $arguments[0];
            return $this->skautIS->$name($arguments);
        } catch (SkautIS_AuthenticationException $exc) {
            Environment::getUser()->logout(TRUE);
            $presenter = Environment::getApplication()->getPresenter();
            $presenter->flashMessage("Vypršelo přihlášení do skautISu", "fail");
            $presenter->redirect(":Default:");
        }
    }

    public function __get($name) {
        return $this->skautIS->$name;
    }

}