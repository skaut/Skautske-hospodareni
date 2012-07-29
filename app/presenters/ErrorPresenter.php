<?php

class ErrorPresenter extends Presenter {

    public function renderDefault($exception) {
        if ($this->isAjax()) { // AJAX request? Just note this error in payload.
            $this->payload->error = TRUE;
            $this->terminate();
        } elseif ($exception instanceof BadRequestException) {
            $code = $exception->getCode();
            $this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx'); // load template 403.latte or 404.latte or ... 4xx.latte
        } elseif ($exception instanceof SkautIS_Exception) {
            Debugger::log($exception, Debugger::WARNING); // and log exception
            $this->setView('SkautIS');
            $this->template->ex = $exception;
        } elseif ($exception instanceof SkautIS_AuthenticationException) {//vypršelo přihlášení do SkautISu
            $this->user->logout(TRUE);
            $this->flashMessage("Vypršelo přihlášení do skautISu", "danger");
            $this->redirect(":Default:");
        } else {
            $this->setView('500'); // load template 500.latte
            if($exception instanceof SkautIS_Exception && $exception->getMessage() == "SOAP-ERROR: Encoding: object hasn't 'ID_Login' property"){
                //@TODO: předělat
                $m = new Mail();
                $m->setSubject("Login fail");
                $m->setHtmlBody(
                        "ID_Login: ". $this->context->authService->getLoginId() . 
                        "<br />Role ID: ". $this->context->authService->setRoleId() .
                        "<br />Unit ID:" . $this->context->authService->getUnitId() . "<hr />"
                        );
                $m->addTo("sinacek@gmail.com");
                $m->send();
            }
            Debugger::log($exception, Debugger::ERROR); // and log exception
        }
    }

}
