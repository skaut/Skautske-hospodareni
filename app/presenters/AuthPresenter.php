<?php

class AuthPresenter extends BasePresenter {
    
    
    protected function startup() {
        parent::startup();
        $this->service = SkautIS_Mapper::getInstance();
    }

    /**
     * pokud je uziatel uz prihlasen, staci udelat referesh
     * @param string $backlink 
     * @param bool $final - je to konečné přesměrování? použít při problémem se zacyklením
     */
    public function actionDefault($backlink, $final = FALSE ) {
        if (Environment::getUser()->isLoggedIn()) {
            if ($backlink) {
                $this->getApplication()->restoreRequest($backlink);
            }
            $this->redirect(':Default:');
        } else {//uzivatel neni prihlasen
            $this->redirect(':Default:');
        }
//        else {//uzivatel neni prihlasen
//            
//            $this->redirectUrl("http://test-is.skaut.cz/Login/?appid=".$sservice->getAppId() . (isset($backlink) ? "&ReturnUrl=".$backlink : ""));
//        }
    }

    function actionLogOnSkautIs() {
        $this->redirectUrl($this->service->getLoginUrl($backlink));
        //$this->redirectUrl("http://test-is.skaut.cz/Login/?appid=" . $sservice->getAppId() . (isset($backlink) ? "&ReturnUrl=" . $backlink : ""));
    }

    /**
     * zajistuje spracovani prihlaseni na skautIS
     * @param string $ReturnUrl 
     */
    function actionSkautIS($ReturnUrl = NULL) {
        $post = $this->request->post;
        if (!isset($post['skautIS_Token'])) { //pokud není nastavený token, tak zde nemá co dělat
            $this->redirect(":Default:");
        }
        try {
            //'token' => $param['skautIS_Token'],
            //'IDRole' => $param['skautIS_IDRole'],
            //'IDUnit' => $param['skautIS_IDUnit'],
            $this->service->setToken($post['skautIS_Token'])
                    ->setRoleId($post['skautIS_IDRole'])
                    ->setUnitId($post['skautIS_IDUnit']);
            
            if (!$this->service->isLoggedIn()) {
                throw new SkautIS_AuthenticationException("Nemáte platné přihlášení do skautISu");
            }
            $me = $this->service->getMyDetail();

            $this->user->setExpiration('+ 30 minutes'); // nastavíme expiraci
            $this->user->setAuthenticator(new SkautISAuthenticator());
            $this->user->login($me->ID);

            if (isset($ReturnUrl)) {
                Environment::getApplication()->restoreRequest($ReturnUrl);
            }

            $this->presenter->redirect(':Default:');
        } catch (SkautIS_AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), "fail");
            $this->redirect(":Auth:");
        } catch (AuthenticationException $e) {
            $this->redirect(":SkautIS:Registration:");
        }
    }

    /**
     * zajistuje odhlaseni ze skautISu
     */
    function actionSkautISLogOut() {
        $this->service->logout();
        Environment::getUser()->logout(TRUE);
        if ($this->request->post['skautIS_Logout']) {
            $this->presenter->flashMessage("Byl jsi úspěšně odhlášen.");
        } else {
            $this->presenter->flashMessage("Odhlášení ze skautISu se nezdařilo", "fail");
        }
        //$this->redirectUrl("https://is.skaut.cz/Login/LogOut.aspx?appid=" . $this->service->getAppId() . "&token=". $this->service->getToken());
        $this->redirect(":Default:");
    }

}
