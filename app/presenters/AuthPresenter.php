<?php

namespace App;

use Skautis\Wsdl\AuthenticationException;

class AuthPresenter extends BasePresenter {

    /**
     *
     * @var \Model\AuthService
     */
    protected $authService;

    public function __construct(\Model\AuthService $as) {
        parent::__construct();
        $this->authService = $as;
    }

    /**
     * pokud je uziatel uz prihlasen, staci udelat referesh
     * @param string $backlink 
     */
    public function actionDefault($backlink) {
//        if ($this->user->isLoggedIn()) {
//            if ($backlink) {
//                $this->restoreRequest($backlink);
//            }
//        }
        $this->redirect(':Default:');
    }

    /**
     * přesměruje na stránku s přihlášením
     * @param string $backlink
     */
    function actionLogOnSkautIs($backlink = NULL) {
        $this->redirectUrl($this->authService->getLoginUrl($backlink));
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
            $this->authService->setInit(array(
                "token" => $post['skautIS_Token'],
                "roleId" => $post['skautIS_IDRole'],
                "unitId" => $post['skautIS_IDUnit']
            ));

            if (!$this->userService->isLoggedIn()) {
                throw new AuthenticationException("Nemáte platné přihlášení do skautisu");
            }
            $me = $this->userService->getPersonalDetail();

            $this->user->setExpiration('+ 29 minutes'); // nastavíme expiraci
            $this->user->setAuthenticator(new \Sinacek\SkautisAuthenticator());
            $this->user->login($me);

            $this->updateUserAccess();

            if (isset($ReturnUrl)) {
                $this->restoreRequest($ReturnUrl);
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
     * zajištuje odhlašení ze skautisu
     * Skautis sem přesměruje po svém odhlášení
     */
    function actionLogoutSIS() {
        $this->redirectUrl($this->authService->getLogoutUrl());
    }

    function actionSkautisLogout() {
        $this->user->logout(TRUE);
        if (isset($this->request->post['skautIS_Logout'])) {
            $this->presenter->flashMessage("Byl jsi úspěšně odhlášen.");
        } else {
            $this->presenter->flashMessage("Odhlášení ze skautisu se nezdařilo", "danger");
        }
        $this->redirect(":Default:");
        //$this->redirectUrl($this->service->getLogoutUrl());
    }

}
