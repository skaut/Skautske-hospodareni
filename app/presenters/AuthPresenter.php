<?php

declare(strict_types=1);

namespace App;

use Model\AuthService;
use Sinacek\SkautisAuthenticator;
use Skautis\Wsdl\AuthenticationException;
use function strlen;
use function substr;

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
        if ($backlink !== null) {
            $backlink = $this->getHttpRequest()->getUrl()->getBaseUrl() . $backlink;
        }
        $this->redirectUrl($this->authService->getLoginUrl($backlink));
    }

    /**
     * zajistuje zpracovani prihlaseni na skautIS
     */
    public function actionSkautIS(?string $ReturnUrl = null) : void
    {
        $post = $this->getRequest()->getPost();
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

            $this->getUser()->setExpiration('+ 29 minutes'); // nastavíme expiraci
            $this->getUser()->setAuthenticator(new SkautisAuthenticator());
            $this->getUser()->login($me);

            $this->updateUserAccess();

            if ($ReturnUrl !== null) {
                $this->restoreRequest(substr($ReturnUrl, strlen($this->getHttpRequest()->getUrl()->getBaseUrl())));
            }
        } catch (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redirect(':Auth:');
        }
        $this->redirect(':Accountancy:Default:');
    }

    public function actionAjax(?string $backlink = null) : void
    {
        $this->template->setParameters(['backlink' => $backlink]);
        $this->flashMessage('Vypršel čas přihlášení. Přihlaste se prosím znovu.', 'warning');
        $this->redrawControl();
    }

    /**
     * zajištuje odhlašení ze skautisu
     * Skautis po svém odhlášení přesměruje na actionSkautisLogout
     */
    public function actionLogoutSIS() : void
    {
        $this->redirectUrl($this->authService->getLogoutUrl());
    }

    /**
     * slouží pouze jako návratová adresa po odhlášení ze skautisu
     */
    public function actionSkautisLogout() : void
    {
        $this->getUser()->logout(true);
        if (isset($this->getRequest()->getPost()['skautIS_Logout'])) {
            $this->flashMessage('Byl jsi úspěšně odhlášen.');
        } else {
            $this->flashMessage('Odhlášení ze skautisu se nezdařilo', 'danger');
        }
        $this->redirect(':Default:');
    }
}
