<?php

declare(strict_types=1);

namespace App\Presentation\InvoiceAccess;

use App\Model\Invoice\InvoiceAccessChecker;
use App\Model\User\Entity\InvoiceAccessRequest;
use App\Model\User\InvoiceAccessNotificationService;
use App\Model\User\Manager\InvoiceAccessRequestManager;
use App\Model\User\Repository\InvoiceAccessRequestRepository;
use Component\Forms\BaseForm;
use Nette\Application\AbortException;
use Nette\Forms\Form;
use Nette\Utils\Validators;
use stdClass;
use Throwable;

use function get_object_vars;
use function is_string;
use function trim;

trait InvoiceAccessGuard
{
    private InvoiceAccessChecker $invoiceAccessChecker;

    private InvoiceAccessRequestRepository $invoiceAccessRequestRepository;

    private InvoiceAccessRequestManager $invoiceAccessRequestManager;

    private InvoiceAccessNotificationService $invoiceAccessNotificationService;

    public function injectInvoiceAccessGuard(
        InvoiceAccessChecker $invoiceAccessChecker,
        InvoiceAccessRequestRepository $invoiceAccessRequestRepository,
        InvoiceAccessRequestManager $invoiceAccessRequestManager,
        InvoiceAccessNotificationService $invoiceAccessNotificationService,
    ): void {
        $this->invoiceAccessChecker = $invoiceAccessChecker;
        $this->invoiceAccessRequestRepository = $invoiceAccessRequestRepository;
        $this->invoiceAccessRequestManager = $invoiceAccessRequestManager;
        $this->invoiceAccessNotificationService = $invoiceAccessNotificationService;
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

        $values = $form->getValues(\Nette\Utils\ArrayHash::class);
        $request = $this->invoiceAccessRequestManager->createRequest(
            $userId,
            $this->unitId->toInt(),
            $this->userService->getRoleId(),
            $this->resolveInvoiceAccessDisplayName($userId),
            $this->resolveInvoiceAccessRequesterEmail(),
            (string) $values->note,
        );

        try {
            $notificationSent = $this->invoiceAccessNotificationService->notifyRequestReceived($request);
            $this->flashMessage(
                $notificationSent
                    ? 'Žádost o zařazení do testovacího programu byla odeslána.'
                    : 'Žádost o zařazení do testovacího programu byla odeslána. Ve SkautISu ale nemáte uložený e-mail pro potvrzení.',
                $notificationSent ? 'success' : 'warning',
            );
        } catch (Throwable) {
            $this->flashMessage('Žádost o zařazení do testovacího programu byla odeslána, ale potvrzovací e-mail se nepodařilo odeslat.', 'warning');
        }

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

    private function resolveInvoiceAccessRequesterEmail(): ?string
    {
        try {
            $email = $this->resolveEmailFromSkautisDetail(get_object_vars($this->userService->getUserDetail()));
            if ($email !== null) {
                return $email;
            }
        } catch (Throwable) {
            // Fall through to personal detail.
        }

        try {
            return $this->resolveEmailFromSkautisDetail(get_object_vars($this->userService->getPersonalDetail()));
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $detail */
    private function resolveEmailFromSkautisDetail(array $detail): ?string
    {
        foreach (['Email', 'PersonEmail', 'UserEmail', 'Mail'] as $key) {
            $email = $detail[$key] ?? null;
            if (! is_string($email)) {
                continue;
            }

            $email = trim($email);
            if ($email !== '' && Validators::isEmail($email)) {
                return $email;
            }
        }

        return null;
    }
}
