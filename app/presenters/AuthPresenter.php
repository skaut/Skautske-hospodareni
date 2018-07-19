<?php

declare(strict_types=1);

namespace App;

use Model\AuthService;
use Sinacek\SkautisAuthenticator;
use Skautis\Wsdl\AuthenticationException;

class AuthPresenter extends BasePresenter
{
    /** @var AuthService */
    protected $authService;

    public function __construct(AuthService $as)
    {
        parent::__construct();
        $this->authService = $as;
    }

    /**
     * pokud je uziatel uz prihlasen, staci udelat referesh
     */
    public function actionDefault(string $backlink) : void
    {
        $this->redirect(':Default:');
    }

    /**
     * přesměruje na stránku s přihlášením
     */
    public function actionLogOnSkautIs(?string $backlink = null) : void
    {
        $this->redirectUrl($this->authService->getLoginUrl($backlink));
    }

    /**
     * zajistuje zpracovani prihlaseni na skautIS
     */
    public function actionSkautIS(?string $ReturnUrl = null) : void
    {
        $post = $this->request->post;
        if (! isset($post['skautIS_Token'])) { //pokud není nastavený token, tak zde nemá co dělat
            $this->redirect(':Default:');
        }
        try {
            $this->authService->setInit(
                $post['skautIS_Token'],
                (int) $post['skautIS_IDRole'],
                (int) $post['skautIS_IDUnit']
            );

            if (! $this->userService->isLoggedIn()) {
                throw new AuthenticationException('Nemáte platné přihlášení do skautisu');
            }
            $me = $this->userService->getPersonalDetail();

            $this->user->setExpiration('+ 29 minutes'); // nastavíme expiraci
            $this->user->setAuthenticator(new SkautisAuthenticator());
            $this->user->login($me);

            $this->updateUserAccess();

            if (isset($ReturnUrl)) {
                $this->restoreRequest($ReturnUrl);
            }
        } catch (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redirect(':Auth:');
        }
        $this->presenter->redirect(':Accountancy:Default:');
    }

    public function actionAjax(?string $backlink = null) : void
    {
        $this->template->backlink = $backlink;
        $this->flashMessage('Vypršel čas přihlášení. Přihlaste se prosím znovu.', 'warning');
        $this->redrawControl();
    }

    /**
     * zajištuje odhlašení ze skautisu
     * Skautis sem přesměruje po svém odhlášení
     */
    public function actionLogoutSIS() : void
    {
        $this->redirectUrl($this->authService->getLogoutUrl());
    }

    public function actionSkautisLogout() : void
    {
        $this->user->logout(true);
        if (isset($this->request->post['skautIS_Logout'])) {
            $this->presenter->flashMessage('Byl jsi úspěšně odhlášen.');
        } else {
            $this->presenter->flashMessage('Odhlášení ze skautisu se nezdařilo', 'danger');
        }
        $this->redirect(':Default:');
    }
}
