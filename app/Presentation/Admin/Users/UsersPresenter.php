<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Users;

use Component\Forms\BaseForm;
use App\Model\User\Entity\AdminUser;
use App\Model\User\Manager\AdminUserManager;
use App\Model\Admin\Services\AdminAccessChecker;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use App\Model\User\Repository\AdminUserRepository;

final class UsersPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private ?AdminUser $editedAdminUser = null;

    public function __construct(
        private AdminUserRepository $adminUserRepository,
        private AdminUserManager $adminUserManager,
        private AdminAccessChecker $adminAccessChecker,
    ) {
    }

    public function actionDefault(?int $edit = null): void
    {
        if ($edit === null || ! $this->adminUserRepository->isStorageAvailable()) {
            return;
        }

        $adminUser = $this->adminUserRepository->find($edit);

        if (! $adminUser instanceof AdminUser) {
            $this->flashMessage('Požadovaný admin uživatel nebyl nalezen.', 'warning');
            $this->redirect('default');
        }

        $this->editedAdminUser = $adminUser;
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'users',
            'unitId' => $this->unitId->toInt(),
            'adminUsers' => $this->adminUserRepository->findAllOrderedByUserId(),
            'configuredAdminUserIds' => $this->adminAccessChecker->getConfiguredAdminUserIds(),
            'storageAvailable' => $this->adminUserRepository->isStorageAvailable(),
            'editedAdminUser' => $this->editedAdminUser,
        ]);
    }

    public function handleDeleteAdminUser(int $id): void
    {
        if (! $this->adminUserRepository->isStorageAvailable()) {
            $this->flashMessage('Perzistentní tabulka adminů ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $adminUser = $this->adminUserRepository->find($id);

        if (! $adminUser instanceof AdminUser) {
            $this->flashMessage('Požadovaný admin uživatel nebyl nalezen.', 'warning');
            $this->redirect('default');
        }

        $this->adminUserManager->delete($adminUser);
        $this->flashMessage('Admin uživatel byl odebrán.', 'success');
        $this->redirect('default');
    }

    public function createComponentAdminUserForm(): Form
    {
        $form = new BaseForm();
        $form->addInteger('userId', 'User ID')
            ->setRequired('Zadejte user_id administrátora.')
            ->addRule(Form::MIN, 'User ID musí být kladné číslo.', 1);

        $form->addSubmit('submit', $this->editedAdminUser instanceof AdminUser ? 'Uložit změny' : 'Přidat admina');

        if ($this->editedAdminUser instanceof AdminUser) {
            $form->setDefaults([
                'userId' => $this->editedAdminUser->getUserId(),
            ]);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->processAdminUserForm($form);
        };

        return $form;
    }

    /** @throws AbortException */
    private function processAdminUserForm(Form $form): void
    {
        if (! $this->adminUserRepository->isStorageAvailable()) {
            $this->flashMessage('Perzistentní tabulka adminů ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $userId = (int) $form->getValues()->userId;
        $existingAdminUser = $this->adminUserRepository->findOneByUserId($userId);

        if (
            $existingAdminUser instanceof AdminUser
            && (
                ! $this->editedAdminUser instanceof AdminUser
                || $existingAdminUser->getId() !== $this->editedAdminUser->getId()
            )
        ) {
            $this->flashMessage('Tento user_id už mezi administrátory existuje.', 'warning');
            $this->redirect('default', $this->editedAdminUser instanceof AdminUser ? ['edit' => $this->editedAdminUser->getId()] : []);
        }

        if ($this->editedAdminUser instanceof AdminUser) {
            $this->adminUserManager->updateUserId($this->editedAdminUser, $userId);
            $this->flashMessage('Admin uživatel byl upraven.', 'success');
        } else {
            $this->adminUserManager->create($userId);
            $this->flashMessage('Admin uživatel byl přidán.', 'success');
        }

        $this->redirect('default');
    }
}
