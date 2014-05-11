<?php

namespace App;

use Nette,
    WebLoader,
    SkautIS\Exception\AuthenticationException;

abstract class BasePresenter extends Nette\Application\UI\Presenter {

    protected function startup() {
        parent::startup();
        
        //adresář s částmi šablon pro použití ve více modulech
        $this->template->templateBlockDir = APP_DIR . "/templateBlocks/";
        
        $storage = \Nette\Environment::getSession()->getSection("__" . __CLASS__);
        $this->context->skautIS->setStorage($storage, TRUE);
        $this->template->backlink = $this->getParameter("backlink");
        $params = $this->context->getParameters();
        $this->template->ssl = $params['ssl'];

        \Nette\Forms\Container::extensionMethod('addDatePicker', function (\Nette\Forms\Container $container, $name, $label = NULL) {
            return $container[$name] = new \JanTvrdik\Components\DatePicker($label);
        });
        try {
            if ($this->user->isLoggedIn() && $this->context->userService->isLoggedIn()) { //prodluzuje přihlášení při každém požadavku
                $this->context->authService->updateLogoutTime();
            }
        } catch (AuthenticationException $e) {
            if ($this->name != "Auth" || $this->params['action'] != "skautisLogout") { //pokud jde o odhlaseni, tak to nevadi
                throw $e;
            }
        }
    }

    protected function beforeRender() {
        parent::beforeRender();
        if ($this->user->isLoggedIn()) {
            $this->template->myRoles = $this->context->userService->getAllSkautISRoles();
            $this->template->myRole = $this->context->userService->getRoleId();
        }
        $this->template->registerHelperLoader("AccountancyHelpers::loader");
    }

    //změní přihlášenou roli ve skautISu
    public function handleChangeRole($roleId) {
        $this->context->userService->updateSkautISRole($roleId);
        $this->redirect("this");
    }

    public function createComponentCss() {
        $files = new WebLoader\FileCollection(WWW_DIR . '/css');
        $compiler = WebLoader\Compiler::createCssCompiler($files, WWW_DIR . '/webtemp');

        //s minimalizací zlobí bootstrap
//        $compiler->addFilter(new VariablesFilter(array('foo' => 'bar')));        
//        function mini($code) {
//            return CssMin::minify($code);
//        }
//        $compiler->addFilter("mini");
        $control = new WebLoader\Nette\CssLoader($compiler, $this->context->httpRequest->url->baseUrl . 'webtemp');
        $control->setMedia('screen');
        $files->addFiles(array(
            'fancybox/fancybox.css',
            'bootstrap.min.css',
            'bootstrap-responsive.min.css',
            'jquery-ui-1.10.0.custom.css',
            'my-responsive.css',
//            'offline.css',
            'site.css'
        ));
        return $control;
    }

    public function createComponentJs() {
        $files = new WebLoader\FileCollection(WWW_DIR . '/js');
        $compiler = WebLoader\Compiler::createJsCompiler($files, WWW_DIR . '/webtemp');
        $files->addFiles(array(
            'jquery-1.8.2.min.js',
            'jquery-ui-1.10.0.custom.min.js',
            'netteForms.js',
            'jquery.touchwipe.min.js',
            'mobile.js',
            'my-datepicker.js',
            'combobox.js',
            'jquery.nette.js',
            'bootstrap.js',
            'jquery.fancybox.pack.js',
            'live-form-validation.js',
            'jquery.ajaxform.js',
            'offline.js',
            'html5.js',
            'h5utils.js',
            'my.js'
        ));
        return new WebLoader\Nette\JavaScriptLoader($compiler, $this->context->httpRequest->url->baseUrl . 'webtemp');
    }

}
