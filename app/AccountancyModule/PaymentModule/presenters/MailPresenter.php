<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\SmtpUpdateForm;
use App\AccountancyModule\PaymentModule\Factories\ISmtpUpdateFormFactory;
use Model\DTO\Google\OAuth;
use Model\Google\Commands\RemoveOAuth;
use Model\Google\OAuthId;
use Model\Google\ReadModel\Queries\OAuthQuery;
use Model\Google\ReadModel\Queries\UnitOAuthListQuery;
use Model\Mail\Repositories\IGoogleRepository;
use Model\MailService;
use Model\Payment\Commands\RemoveMailCredentials;
use function assert;

class MailPresenter extends BasePresenter
{
    /** @var MailService */
    private $model;

    /** @var ISmtpUpdateFormFactory */
    private $smtpUpdateFormFactory;

    private IGoogleRepository $googleRepository;

    public function __construct(
        MailService $model,
        ISmtpUpdateFormFactory $smtpUpdateFormFactory,
        IGoogleRepository $googleRepository
    ) {
        parent::__construct();
        $this->model                 = $model;
        $this->smtpUpdateFormFactory = $smtpUpdateFormFactory;
        $this->googleRepository      = $googleRepository;
    }

    public function actionDefault(?int $unitId = null) : void
    {
        if ($unitId === null) {
            $this->redirect('this', ['unitId' => $this->unitService->getUnitId()]);
        }

        if (! $this->isEditable) {
            $this->setView('accessDenied');

            return;
        }
        $this->template->setParameters([
            'oauthList'     => $this->queryBus->handle(new UnitOAuthListQuery($this->unitId)),
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

    public function handleRemoveOAuth(string $id) : void
    {
        $oauthId = OAuthId::fromString($id);
        $oauth   = $this->queryBus->handle(new OAuthQuery($oauthId));
        if ($oauth === null) {
            $this->flashMessage('Google OAuth účet nenalezen!', 'warning');
            $this->redirect('default');
        }
        assert($oauth instanceof OAuth);

        if (! $this->isEditable || ! $oauth->getUnitId()->equals($this->unitId)) {
            $this->flashMessage('Nemáte oprávnění mazat OAuth', 'danger');
            $this->redirect('default');
        }

        $this->commandBus->handle(new RemoveOAuth($oauthId));
        $this->flashMessage('Google OAuth účet byl smazán.');
        $this->redirect('default');
    }

    protected function createComponentUpdateForm() : SmtpUpdateForm
    {
        return $this->smtpUpdateFormFactory->create($this->unitId);
    }
}
