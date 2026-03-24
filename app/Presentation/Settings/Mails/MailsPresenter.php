<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Mails;

use App\Model\DTO\Google\OAuth;
use App\Model\Google\Commands\RemoveOAuth;
use App\Model\Google\OAuthId;
use App\Model\Google\ReadModel\Queries\OAuthQuery;
use App\Model\Google\ReadModel\Queries\UnitOAuthListQuery;
use App\Model\Mail\MailService;

final class MailsPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    public function __construct(
        private MailService $model,
    ) {
        parent::__construct();
    }

    public function actionDefault(?int $unitId = null): void
    {
        if ($unitId === null) {
            $this->redirect('default', ['unitId' => $this->unitService->getUnitId()]);
        }

        if (! $this->isEditable) {
            $this->setView('accessDenied');

            return;
        }

        $this->template->setParameters([
            'oauthList' => $this->queryBus->handle(new UnitOAuthListQuery($this->unitId)),
            'list' => $this->model->getAll($this->getEditableUnits()),
            'editableUnits' => $this->getEditableUnits(),
        ]);
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->setSettingsTemplateParameters();
    }

    public function handleRemoveOAuth(string $id): void
    {
        $oauthId = OAuthId::fromString($id);
        $oauth = $this->queryBus->handle(new OAuthQuery($oauthId));
        if ($oauth === null) {
            $this->flashMessage('Google účet nenalezen!', 'warning');
            $this->redirect('default', ['unitId' => $this->getUnitId()]);
        }

        if (! $oauth instanceof OAuth) {
            $this->flashMessage('Google účet nenalezen!', 'warning');
            $this->redirect('default', ['unitId' => $this->getUnitId()]);
        }

        if (! $this->isEditable || ! ($oauth->getUnitId() === $this->unitId->toInt())) {
            $this->flashMessage('Nemáte oprávnění odebírat propojený Google účet', 'danger');
            $this->redirect('default', ['unitId' => $this->getUnitId()]);
        }

        $this->commandBus->handle(new RemoveOAuth($oauthId));
        $this->flashMessage('Propojení Google účtu bylo smazáno.');
        $this->redirect('default', ['unitId' => $this->getUnitId()]);
    }
}
