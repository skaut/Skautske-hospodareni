<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\SmtpAddForm;
use App\AccountancyModule\PaymentModule\Components\SmtpUpdateForm;
use App\AccountancyModule\PaymentModule\Factories\ISmtpAddFormFactory;
use App\AccountancyModule\PaymentModule\Factories\ISmtpUpdateFormFactory;
use Model\MailService;
use Model\Payment\Commands\RemoveMailCredentials;

class MailPresenter extends BasePresenter
{
    private MailService $model;

    private ISmtpAddFormFactory $smtpAddFormFactory;

    private ISmtpUpdateFormFactory $smtpUpdateFormFactory;

    public function __construct(
        MailService $model,
        ISmtpAddFormFactory $smtpAddFormFactory,
        ISmtpUpdateFormFactory $smtpUpdateFormFactory
    ) {
        parent::__construct();
        $this->model                 = $model;
        $this->smtpAddFormFactory    = $smtpAddFormFactory;
        $this->smtpUpdateFormFactory = $smtpUpdateFormFactory;
    }

    public function actionDefault(int $unitId) : void
    {
        if (! $this->isEditable) {
            $this->setView('accessDenied');

            return;
        }

        $this->template->setParameters([
            'list'          => $this->model->getAll($this->getEditableUnitIds()),
            'editableUnits' => $this->getEditableUnits(),
        ]);
    }

    public function handleEdit(int $id) : void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění měnit smtp', 'danger');
        $this->redirect('this');
    }

    public function handleRemove(int $id) : void
    {
        $mail = $this->model->get($id);

        if ($mail === null) {
            $this->flashMessage('Zadaný email neexistuje', 'danger');
            $this->redirect('this');
        }

        if (! $this->isEditable || $mail->getUnitId() !== $this->unitId->toInt()) {
            $this->flashMessage('Nemáte oprávnění mazat smtp', 'danger');
            $this->redirect('this');
        }

        $this->commandBus->handle(new RemoveMailCredentials($id));
    }

    protected function createComponentAddForm() : SmtpAddForm
    {
        return $this->smtpAddFormFactory->create($this->unitId, (int) $this->getUser()->getId());
    }

    protected function createComponentUpdateForm() : SmtpUpdateForm
    {
        return $this->smtpUpdateFormFactory->create($this->unitId);
    }
}
