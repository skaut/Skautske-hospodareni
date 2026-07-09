<?php

declare(strict_types=1);

namespace App;

use App\Model\Auth\AuthService;
use App\Model\User\UserPreferencesService;
use Nette\Security\SimpleIdentity;
use Skautis\Wsdl\AuthenticationException;

use function array_map;
use function strlen;
use function substr;

class AuthPresenter extends BasePresenter
{
    protected AuthService $authService;

    public function __construct(
        AuthService $as,
        private UserPreferencesService $preferences,
    ) {
        parent::__construct();

        $this->authService = $as;
    }

    /**
     * pokud je uziatel uz prihlasen, staci udelat referesh.
     */
    public function actionDefault(?string $backlink = null): void
    {
        $this->redirect(':Default:');
    }

    /**
     * přesměruje na stránku s přihlášením.
     */
    public function actionLogOnSkautIs(?string $backlink = null): void
    {
        if ($backlink !== null) {
            $backlink = $this->getHttpRequest()->getUrl()->getBaseUrl().$backlink;
        }

        $this->redirectUrl($this->authService->getLoginUrl($backlink));
    }

    /**
     * zajistuje zpracovani prihlaseni na skautIS.
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    public function actionSkautIS(?string $ReturnUrl = null): void
    {
        $post = $this->getRequest()->getPost();
        if (! isset($post['skautIS_Token'])) { // pokud není nastavený token, tak zde nemá co dělat
            $this->redirect(':Default:');
        }

        try {
            $this->authService->setInit(
                $post['skautIS_Token'],
                (int) $post['skautIS_IDRole'],
                (int) $post['skautIS_IDUnit'],
            );

            if (! $this->userService->isLoggedIn()) {
                throw new AuthenticationException('Nemáte platné přihlášení do skautisu');
            }

            $user = $this->getUser();
            $userId = (int) $this->userService->getUserDetail()->ID;
            $roles = $this->userService->getAllSkautisRoles();
            $rememberedRoleId = $this->preferences->getRememberedSkautisRoleIdForLogin(
                $userId,
                array_map(static fn (object $role): int => (int) $role->ID, $roles),
            );
            if ($rememberedRoleId !== null && $rememberedRoleId !== $this->userService->getRoleId()) {
                $this->userService->updateSkautISRole($rememberedRoleId);
            }

            $user->setExpiration('+ 29 minutes'); // nastavíme expiraci
            $user->login(new SimpleIdentity(
                $userId,
                $roles,
                ['currentRole' => $this->userService->getActualRole()],
            ));

            $this->updateUserAccess();

            if ($ReturnUrl !== null) {
                $this->restoreRequest(substr($ReturnUrl, strlen($this->getHttpRequest()->getUrl()->getBaseUrl())));
            }
        } catch (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redirect(':Auth:');
        }

        $this->redirect('Dashboard:default');
    }

    public function actionAjax(?string $backlink = null): void
    {
        $this->template->setParameters(['backlink' => $backlink]);
        $this->flashMessage('Vypršel čas přihlášení. Přihlaste se prosím znovu.', 'warning');
        $this->redrawControl();
    }

    /**
     * zajištuje odhlašení ze skautisu
     * Skautis po svém odhlášení přesměruje na actionSkautisLogout.
     */
    public function actionLogoutSIS(): void
    {
        $this->redirectUrl($this->authService->getLogoutUrl());
    }

    /**
     * slouží pouze jako návratová adresa po odhlášení ze skautisu.
     */
    public function actionSkautisLogout(): void
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
