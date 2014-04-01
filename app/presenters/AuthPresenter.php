<?php

use SkautIS\Exception\AuthenticationException;

class AuthPresenter extends BasePresenter {

    protected function startup() {
        parent::startup();
    }

    /**
     * pokud je uziatel uz prihlasen, staci udelat referesh
     * @param string $backlink 
     */
    public function actionDefault($backlink) {
        if (\Nette\Environment::getUser()->isLoggedIn()) {
            if ($backlink) {
                $this->restoreRequest($backlink);
            }
        }
        $this->redirect(':Default:');
    }

    /**
     * přesměruje na stránku s přihlášením
     * @param string $backlink
     */
    function actionLogOnSkautIs($backlink = NULL) {
        $this->redirectUrl($this->context->authService->getLoginUrl($backlink));
    }

    /**
     * zajistuje zpracovani prihlaseni na skautIS
     * @param string $ReturnUrl 
     */
    function actionSkautIS($ReturnUrl = NULL) {
        $post = $this->request->post;
        if (!isset($post['skautIS_Token'])) { //pokud není nastavený token, tak zde nemá co dělat
            $this->redirect(":Default:");
        }
//        Nette\Diagnostics\Debugger::log("AuthP: ".$post['skautIS_Token']." / ". $post['skautIS_IDRole'] . " / " . $post['skautIS_IDUnit'], "auth");
        try {
            $this->context->authService->setInit(array(
                "token" => $post['skautIS_Token'],
                "roleId" => $post['skautIS_IDRole'],
                "unitId" => $post['skautIS_IDUnit']
            ));

            if (!$this->context->userService->isLoggedIn()) {
                throw new AuthenticationException("Nemáte platné přihlášení do skautISu");
            }
            $me = $this->context->userService->getPersonalDetail();

            $this->user->setExpiration('+ 29 minutes'); // nastavíme expiraci
            $this->user->setAuthenticator(new SkautISAuthenticator());
            $this->user->login($me);

            if (isset($ReturnUrl)) {
                $this->context->application->restoreRequest($ReturnUrl);
            }
        } catch (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), "danger");
            $this->redirect(":Auth:");
        }
        $this->presenter->redirect(':Accountancy:Default:');
    }
    
    function actionAjax($backlink = NULL) {
        $this->template->backlink = $backlink;
        $this->flashMessage("Vypršel čas přihlášení. Přihlaste se prosím znovu.", "warning");
        $this->invalidateControl();
    }

    /**
     * zajištuje odhlašení ze skautISu
     * SkautIS sem přesměruje po svém odhlášení
     */
    function actionLogoutSIS() {
        $this->redirectUrl($this->context->authService->getLogoutUrl());
    }
    
    function actionSkautisLogout() {
        $this->user->logout(TRUE);
        if ($this->request->post['skautIS_Logout']) {
            $this->presenter->flashMessage("Byl jsi úspěšně odhlášen.");
        } else {
            $this->presenter->flashMessage("Odhlášení ze skautISu se nezdařilo", "danger");
        }
        $this->redirect(":Default:");
        //$this->redirectUrl($this->service->getLogoutUrl());
    }

}
