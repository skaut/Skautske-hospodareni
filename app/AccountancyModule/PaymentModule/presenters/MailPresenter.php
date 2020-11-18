<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\DTO\Google\OAuth;
use Model\Google\Commands\RemoveOAuth;
use Model\Google\OAuthId;
use Model\Google\ReadModel\Queries\OAuthQuery;
use Model\Google\ReadModel\Queries\UnitOAuthListQuery;
use Model\MailService;
use function assert;

class MailPresenter extends BasePresenter
{
    /** @var MailService */
    private $model;

    public function __construct(
        MailService $model
    ) {
        parent::__construct();
        $this->model = $model;
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
            $this->flashMessage('Google účet nenalezen!', 'warning');
            $this->redirect('default');
        }
        assert($oauth instanceof OAuth);

        if (! $this->isEditable || ! ($oauth->getUnitId() === $this->unitId->toInt())) {
            $this->flashMessage('Nemáte oprávnění odebírat propojený Google účet', 'danger');
            $this->redirect('default');
        }

        $this->commandBus->handle(new RemoveOAuth($oauthId));
        $this->flashMessage('Propojení Google účtu bylo smazáno.');
        $this->redirect('default');
    }
}
