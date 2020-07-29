<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\DTO\Google\OAuth;
use Model\Google\Commands\RemoveOAuth;
use Model\Google\OAuthId;
use Model\Google\ReadModel\Queries\OAuthQuery;
use Model\Google\ReadModel\Queries\UnitOAuthListQuery;
use Model\Mail\Repositories\IGoogleRepository;
use Model\MailService;
use function assert;

class MailPresenter extends BasePresenter
{
    /** @var MailService */
    private $model;

    private IGoogleRepository $googleRepository;

    public function __construct(
        MailService $model,
        IGoogleRepository $googleRepository
    ) {
        parent::__construct();
        $this->model            = $model;
        $this->googleRepository = $googleRepository;
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
}
