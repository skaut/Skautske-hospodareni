<?php

abstract class BasePresenter extends Presenter {

    public $oldLayoutMode = FALSE;
    public $oldModuleMode = FALSE;
    public $user;
    protected $model;
    protected $service;

    protected function startup() {
        parent::startup();
        // Zapnutí session (pokud neběží)
        if (!Environment::getSession()->isStarted()) {
            Environment::getSession()->start();
        }
        RequestsPanel::register();
        
        SkautIS_Mapper::getInstance($this->context->parameters['skautisid']);
        

        //model
//        $model = $this->getName() . "Model";
//        $model = str_replace(":", "_", $model);
//        if (class_exists($model))
//            $this->model = new $model;
//        else
//            $this->model = new BaseModel();


        $this->user = Environment::getUser();

        if (!function_exists("FormContainer_addDatePicker")) {
            function FormContainer_addDatePicker(FormContainer $container, $name, $label = NULL) {
                return $container[$name] = new DatePicker($label);
            }
            FormContainer::extensionMethod('Form::addDatePicker', 'FormContainer_addDatePicker');
        }
        
    }

    public function accessFail() {
        $this->flashMessage("Nemáte oprávnění pro tuto akci", "fail");
        $this->redirect(":Auth:", $this->getApplication()->storeRequest());
    }

    public function handleLogOut() {
            $this->user->logout();
//$skautisService = new SkautisService();
        //$this->redirectUrl("http://test-is.skaut.cz/Login/Logout.aspx?AppID=" . $skautisService->getAppId() . "&token=" . $skautisService->getToken());
    }

    protected function createComponentVp() {
        return new VisualPaginator();
    }
    
    protected function createComponentAuth() {
        return new LoginFormControl();
    }

    protected function createComponent($name) {
        $newName = ucfirst($name) . "Control";
        if (class_exists($newName)) {
            $this[$name] = new $newName;
        } else {
            return parent::createComponent($name);
        }
    }

}
