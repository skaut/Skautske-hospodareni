<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\Skautis\SkautisErrors;
use App\Model\Unit\UnitService;
use App\Model\User\UserPreferencesService;
use App\Model\User\UserService;
use LogicException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Skautis\Wsdl\AuthenticationException;
use Skautis\Wsdl\WsdlException;

final class LoginPanel extends BaseControl
{
    public function __construct(
        private UserService $userService,
        private UnitService $unitService,
        private UserPreferencesService $preferences,
        private User $user,
    ) {
    }

    public function handleChangeRole(int $roleId): void
    {
        try {
            $this->userService->updateSkautISRole($roleId);
        } catch (AuthenticationException $exception) {
            throw $exception; // an expired login goes to the sign-in page
        } catch (WsdlException $exception) {
            if (SkautisErrors::isNoAnswer($exception)) {
                throw $exception; // the error page tells that skautIS is unavailable
            }

            // skautIS answered and refused, e.g. a role the user no longer has (one that expired, a link from an older page).
            $this->flashMessage('Na vybranou roli se nepodařilo přepnout, skautIS ji odmítl. Možná už neplatí, vyberte prosím jinou.', 'warning');
            $this->redirect('this');
        }

        $currentRoleId = $this->userService->getRoleId();
        if ($currentRoleId !== null) {
            $this->preferences->rememberCurrentSkautisRole($currentRoleId);
        }

        $identity = $this->user->getIdentity();

        if (! $identity instanceof SimpleIdentity) {
            throw new LogicException('Assertion failed.');
        }
        $identity->access = $this->userService->getAccessArrays($this->unitService);
        $identity->currentRole = $this->userService->getActualRole();

        $this->redirect('this');
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/LoginPanel.latte');
        if ($this->user->isLoggedIn()) {
            $roles = [];

            foreach ($this->userService->getAllSkautisRoles() as $role) {
                $roles[$role->ID] = $role->DisplayName;
            }

            $this->template->setParameters([
                'roles' => $roles,
                'currentRoleId' => $this->userService->getRoleId(),
            ]);
        }

        $this->template->render();
    }
}
