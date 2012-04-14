<?php

abstract class BasePresenter extends Presenter {

//    public $oldLayoutMode = FALSE;
//    public $oldModuleMode = FALSE;

    protected function startup() {
        parent::startup();
        // Zapnutí session (pokud neběží)
        if (!Environment::getSession()->isStarted()) {
            Environment::getSession()->start();
        }
        RequestsPanel::register();

        $skautis = SkautIS::getInstance($this->context->parameters['skautisid']);

        $this->template->backlink = $this->getParameter("backlink");

        if (!function_exists("FormContainer_addDatePicker")) {

            function FormContainer_addDatePicker(FormContainer $container, $name, $label = NULL) {
                return $container[$name] = new DatePicker($label);
            }

            FormContainer::extensionMethod('Form::addDatePicker', 'FormContainer_addDatePicker');
        }
    }

}
