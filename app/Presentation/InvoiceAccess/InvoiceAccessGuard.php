<?php

declare(strict_types=1);

namespace App\Presentation\InvoiceAccess;

use App\Model\Invoice\InvoiceAccessChecker;
use App\Model\User\Entity\InvoiceAccessRequest;
use App\Model\User\Manager\InvoiceAccessRequestManager;
use App\Model\User\Repository\InvoiceAccessRequestRepository;
use Component\Forms\BaseForm;
use Nette\Application\AbortException;
use Nette\Forms\Form;
use stdClass;

use function is_string;

trait InvoiceAccessGuard
{
    private InvoiceAccessChecker $invoiceAccessChecker;

    private InvoiceAccessRequestRepository $invoiceAccessRequestRepository;

    private InvoiceAccessRequestManager $invoiceAccessRequestManager;

    public function injectInvoiceAccessGuard(
        InvoiceAccessChecker $invoiceAccessChecker,
        InvoiceAccessRequestRepository $invoiceAccessRequestRepository,
        InvoiceAccessRequestManager $invoiceAccessRequestManager,
    ): void {
        $this->invoiceAccessChecker = $invoiceAccessChecker;
        $this->invoiceAccessRequestRepository = $invoiceAccessRequestRepository;
        $this->invoiceAccessRequestManager = $invoiceAccessRequestManager;
    }

    /**
     * @throws AbortException
     */
    protected function startup(): void
    {
        parent::startup();

        if ($this->getAction() === 'earlyAccess' || $this->invoiceAccessChecker->isCurrentUserAllowed()) {
            return;
        }

        $this->forward('earlyAccess');
    }

    public function actionEarlyAccess(): void
    {
    }

    public function renderEarlyAccess(): void
    {
        $userId = $this->invoiceAccessChecker->getCurrentUserId();
        $openRequest = $userId === null ? null : $this->invoiceAccessRequestRepository->findOpenByUserId($userId);

        $this->template->setFile(__DIR__.'/earlyAccess.latte');
        $this->template->setParameters([
            'invoiceAccessRequestStorageAvailable' => $this->invoiceAccessRequestRepository->isStorageAvailable(),
            'hasOpenInvoiceAccessRequest' => $openRequest instanceof InvoiceAccessRequest,
        ]);
    }

    protected function createComponentInvoiceAccessRequestForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addTextArea('note', 'Poznámka')
            ->setRequired(false)
            ->setHtmlAttribute('rows', 4)
            ->setOption('description', 'Napište stručně, jak chcete fakturaci používat. Pole můžete nechat prázdné.');
        $form->addSubmit('send', 'Odeslat žádost');

        $form->onSuccess[] = function (Form $form): void {
            $this->processInvoiceAccessRequestForm($form);
        };

        return $form;
    }

    private function processInvoiceAccessRequestForm(Form $form): void
    {
        if (! $this->invoiceAccessRequestRepository->isStorageAvailable()) {
            $this->flashMessage('Žádost teď nelze uložit, protože databázová tabulka ještě není dostupná.', 'danger');
            $this->redirect('default');
        }

        $userId = $this->invoiceAccessChecker->getCurrentUserId();
        if ($userId === null) {
            $this->flashMessage('Žádost nelze odeslat bez přihlášeného uživatele.', 'danger');
            $this->redirect('default');
        }

        if ($this->invoiceAccessRequestRepository->findOpenByUserId($userId) instanceof InvoiceAccessRequest) {
            $this->flashMessage('Žádost o zařazení do testovacího programu už je odeslaná.', 'info');
            $this->redirect('default');
        }

        $values = $form->getValues();
        $this->invoiceAccessRequestManager->createRequest(
            $userId,
            $this->unitId->toInt(),
            $this->userService->getRoleId(),
            $this->resolveInvoiceAccessDisplayName($userId),
            (string) $values->note,
        );

        $this->flashMessage('Žádost o zařazení do testovacího programu byla odeslána.', 'success');
        $this->redirect('default');
    }

    private function resolveInvoiceAccessDisplayName(int $userId): string
    {
        $userDetail = $this->userService->getUserDetail();
        if (! $userDetail instanceof stdClass) {
            return 'user_id '.$userId;
        }

        foreach (['DisplayName', 'UserName', 'Person', 'Email'] as $property) {
            $value = $userDetail->{$property} ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'user_id '.$userId;
    }
}
