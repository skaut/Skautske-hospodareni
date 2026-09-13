<?php

declare(strict_types=1);

namespace App\Presentation\Admin\InvoiceAccess;

use App\Model\Invoice\InvoiceAccessChecker;
use App\Model\User\Entity\InvoiceAccessRequest;
use App\Model\User\Entity\InvoiceAccessUser;
use App\Model\User\InvoiceAccessNotificationService;
use App\Model\User\Manager\InvoiceAccessRequestManager;
use App\Model\User\Manager\InvoiceAccessUserManager;
use App\Model\User\Repository\InvoiceAccessRequestRepository;
use App\Model\User\Repository\InvoiceAccessUserRepository;
use Component\Forms\BaseForm;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Throwable;

final class InvoiceAccessPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    public function __construct(
        private InvoiceAccessUserRepository $accessUserRepository,
        private InvoiceAccessUserManager $accessUserManager,
        private InvoiceAccessRequestRepository $requestRepository,
        private InvoiceAccessRequestManager $requestManager,
        private InvoiceAccessChecker $invoiceAccessChecker,
        private InvoiceAccessNotificationService $notificationService,
    ) {
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'invoiceAccess',
            'unitId' => $this->unitId->toInt(),
            'accessUsers' => $this->accessUserRepository->findAllOrderedByUserId(),
            'openRequests' => $this->requestRepository->findOpenOrderedByCreatedAt(),
            'configuredAllowedUserIds' => $this->invoiceAccessChecker->getConfiguredAllowedUserIds(),
            'storageAvailable' => $this->accessUserRepository->isStorageAvailable() && $this->requestRepository->isStorageAvailable(),
        ]);
    }

    public function handleApproveRequest(int $id): void
    {
        if (! $this->requestRepository->isStorageAvailable() || ! $this->accessUserRepository->isStorageAvailable()) {
            $this->flashMessage('Tabulky pro správu přístupů ještě nejsou dostupné. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $request = $this->requestRepository->find($id);
        if (! $request instanceof InvoiceAccessRequest) {
            $this->flashMessage('Žádost nebyla nalezena.', 'warning');
            $this->redirect('default');
        }

        $this->requestManager->approve($request);
        try {
            $notificationSent = $this->notificationService->notifyAccessApproved($request);
            $this->flashMessage(
                $notificationSent
                    ? 'Přístup k fakturaci byl schválen a žadatel byl informován e-mailem.'
                    : 'Přístup k fakturaci byl schválen, ale u žádosti není uložený e-mail žadatele.',
                $notificationSent ? 'success' : 'warning',
            );
        } catch (Throwable) {
            $this->flashMessage('Přístup k fakturaci byl schválen, ale e-mail žadateli se nepodařilo odeslat.', 'warning');
        }

        $this->redirect('default');
    }

    public function handleRejectRequest(int $id): void
    {
        if (! $this->requestRepository->isStorageAvailable()) {
            $this->flashMessage('Tabulka žádostí ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $request = $this->requestRepository->find($id);
        if (! $request instanceof InvoiceAccessRequest) {
            $this->flashMessage('Žádost nebyla nalezena.', 'warning');
            $this->redirect('default');
        }

        $this->requestManager->reject($request);
        $this->flashMessage('Žádost byla zamítnuta.', 'success');
        $this->redirect('default');
    }

    public function handleDeleteAccessUser(int $id): void
    {
        if (! $this->accessUserRepository->isStorageAvailable()) {
            $this->flashMessage('Tabulka povolených uživatelů ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $accessUser = $this->accessUserRepository->find($id);
        if (! $accessUser instanceof InvoiceAccessUser) {
            $this->flashMessage('Povolený uživatel nebyl nalezen.', 'warning');
            $this->redirect('default');
        }

        $this->accessUserManager->delete($accessUser);
        $this->flashMessage('Přístup k fakturaci byl odebrán.', 'success');
        $this->redirect('default');
    }

    public function createComponentInvoiceAccessUserForm(): Form
    {
        $form = new BaseForm();
        $form->addInteger('userId', 'User ID')
            ->setRequired('Zadejte user_id uživatele.')
            ->addRule(Form::MIN, 'User ID musí být kladné číslo.', 1);
        $form->addSubmit('submit', 'Povolit přístup');

        $form->onSuccess[] = function (Form $form): void {
            $this->processInvoiceAccessUserForm($form);
        };

        return $form;
    }

    /** @throws AbortException */
    private function processInvoiceAccessUserForm(Form $form): void
    {
        if (! $this->accessUserRepository->isStorageAvailable()) {
            $this->flashMessage('Tabulka povolených uživatelů ještě není dostupná. Spusťte nejdřív migraci databáze.', 'danger');
            $this->redirect('default');
        }

        $userId = (int) $form->getValues(\Nette\Utils\ArrayHash::class)->userId;
        if ($this->accessUserRepository->hasUserId($userId)) {
            $this->flashMessage('Tento user_id už má přístup k fakturaci povolený.', 'warning');
            $this->redirect('default');
        }

        $this->accessUserManager->create($userId);
        $this->flashMessage('Přístup k fakturaci byl povolen.', 'success');
        $this->redirect('default');
    }
}
