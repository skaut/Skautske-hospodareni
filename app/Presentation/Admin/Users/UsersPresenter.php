<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Users;

use App\Model\Admin\Services\AdminAccessChecker;
use App\Model\Common\Repositories\IUserRepository;
use App\Model\User\Enum\SystemRole;
use App\Model\User\Manager\SystemUserRoleManager;
use App\Model\User\Repository\SystemUserRoleRepository;
use Component\Forms\BaseForm;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Throwable;

use function array_map;
use function count;
use function implode;
use function sprintf;

final class UsersPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private ?int $editedUserId = null;

    public function __construct(
        private SystemUserRoleRepository $systemUserRoleRepository,
        private SystemUserRoleManager $systemUserRoleManager,
        private AdminAccessChecker $adminAccessChecker,
        private IUserRepository $userRepository,
    ) {
    }

    public function actionDefault(?int $edit = null): void
    {
        if ($edit === null || ! $this->systemUserRoleRepository->isStorageAvailable()) {
            return;
        }

        if (! $this->systemUserRoleRepository->hasUserId($edit)) {
            $this->flashMessage('Požadovaný uživatel nebyl nalezen.', 'warning');
            $this->redirect('default');
        }

        $this->editedUserId = $edit;
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'users',
            'unitId' => $this->unitId->toInt(),
            'managedUsers' => $this->getManagedUsers(),
            'configuredAdminUserIds' => $this->adminAccessChecker->getConfiguredAdminUserIds(),
            'storageAvailable' => $this->systemUserRoleRepository->isStorageAvailable(),
            'editedUserId' => $this->editedUserId,
        ]);
    }

    public function handleDeleteUserRoles(int $userId): void
    {
        if (! $this->systemUserRoleRepository->isStorageAvailable()) {
            $this->flashMessage('Tabulka systémových rolí ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        if (! $this->systemUserRoleRepository->hasUserId($userId)) {
            $this->flashMessage('Požadovaný uživatel nebyl nalezen.', 'warning');
            $this->redirect('default');
        }

        $this->systemUserRoleManager->removeAllForUser($userId);
        $this->flashMessage('Všechny systémové role uživatele byly odebrány.', 'success');
        $this->redirect('default');
    }

    public function createComponentUserRolesForm(): Form
    {
        $form = new BaseForm();
        if ($this->editedUserId === null) {
            $form->addInteger('userId', 'User ID')
                ->setRequired('Zadejte user_id uživatele.')
                ->addRule(Form::MIN, 'User ID musí být kladné číslo.', 1);
        }
        $form->addCheckboxList('roles', 'Role', SystemRole::options())
            ->setRequired('Vyberte alespoň jednu roli.');
        $form->addSubmit('submit', $this->editedUserId === null ? 'Přidat uživatele' : 'Uložit role');

        if ($this->editedUserId !== null) {
            $form->setDefaults([
                'roles' => array_map(
                    static fn (SystemRole $role): string => $role->value,
                    $this->systemUserRoleRepository->findAllGroupedByUserId()[$this->editedUserId] ?? [],
                ),
            ]);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->processUserRolesForm($form);
        };

        return $form;
    }

    /** @throws AbortException */
    private function processUserRolesForm(Form $form): void
    {
        if (! $this->systemUserRoleRepository->isStorageAvailable()) {
            $this->flashMessage('Tabulka systémových rolí ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $values = $form->getValues();
        $userId = $this->editedUserId ?? (int) $values->userId;
        if ($this->editedUserId === null && $this->systemUserRoleRepository->hasUserId($userId)) {
            $this->flashMessage('Tento user_id už má přiřazené systémové role.', 'warning');
            $this->redirect('default');
        }

        $roles = array_map(
            static fn (string $role): SystemRole => SystemRole::from($role),
            (array) $values->roles,
        );
        $this->systemUserRoleManager->replaceRoles($userId, $roles);
        $this->flashMessage($this->editedUserId === null ? 'Uživateli byly přiřazeny systémové role.' : 'Systémové role uživatele byly upraveny.', 'success');
        $this->redirect('default');
    }

    /** @return array<int, array{userId: int, name: string, roles: string}> */
    private function getManagedUsers(): array
    {
        $managedUsers = [];
        foreach ($this->systemUserRoleRepository->findAllGroupedByUserId() as $userId => $roles) {
            $managedUsers[] = [
                'userId' => $userId,
                'name' => $this->resolveUserName($userId),
                'roles' => count($roles) > 3
                    ? sprintf('%d rolí', count($roles))
                    : implode(', ', array_map(static fn (SystemRole $role): string => $role->label(), $roles)),
            ];
        }

        return $managedUsers;
    }

    private function resolveUserName(int $userId): string
    {
        try {
            return $this->userRepository->find($userId)->getName();
        } catch (Throwable) {
            return 'Nedostupné ve SkautISu';
        }
    }
}
