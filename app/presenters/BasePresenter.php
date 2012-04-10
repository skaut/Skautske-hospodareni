<?php

abstract class BasePresenter extends Presenter {

    public $oldLayoutMode = FALSE;
    public $oldModuleMode = FALSE;
    protected $service;
    
    /**
     * backlink
     */
    protected $backlink;

    protected function startup() {
        parent::startup();
        // Zapnutí session (pokud neběží)
        if (!Environment::getSession()->isStarted()) {
            Environment::getSession()->start();
        }
        RequestsPanel::register();
        
        $skautis = SkautIS::getInstance($this->context->parameters['skautisid']);
        

        if (!function_exists("FormContainer_addDatePicker")) {
            function FormContainer_addDatePicker(FormContainer $container, $name, $label = NULL) {
                return $container[$name] = new DatePicker($label);
            }
            FormContainer::extensionMethod('Form::addDatePicker', 'FormContainer_addDatePicker');
        }
        
        if (!function_exists("FormContainer_addUserFinder")) {
            function FormContainer_addUserFinder(FormContainer $container, $name, $label = NULL) {
                return $container[$name] = new UserFinder($label);
            }
            FormContainer::extensionMethod('Form::addUserFinder', 'FormContainer_addUserFinder');
        }
        
    }

    protected function beforeRender() {
        parent::beforeRender();
        $this->template->backlink = $this->context->httpRequest->getQuery("backlink");   
    }

//    public function accessFail() {
//        $this->flashMessage("Nemáte oprávnění pro tuto akci", "fail");
//        $this->redirect(":Auth:", $this->getApplication()->storeRequest());
//    }

    protected function createComponentVp() {
        return new VisualPaginator();
    }
    
    protected function createComponentAuth() {
        return new LoginFormControl();
    }

//    protected function createComponent($name) {
//        $newName = ucfirst($name) . "Control";
//        if (class_exists($newName)) {
//            $this[$name] = new $newName;
//        } else {
//            return parent::createComponent($name);
//        }
//    }

}
