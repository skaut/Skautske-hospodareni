<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Google;

use App\Model\Common\UnitId;
use App\Model\Google\Commands\SaveOAuth;
use App\Model\Google\ReadModel\Queries\OAuthUrlQuery;
use App\Presentation\Settings\SettingsBasePresenter;
use InvalidArgumentException;

final class GooglePresenter extends SettingsBasePresenter
{
    public function actionOAuth(): void
    {
        $this->redirectUrl($this->queryBus->handle(new OAuthUrlQuery()));
    }

    public function actionToken(string $code): void
    {
        $unitId = $this->userService->getActualRole()->getUnitId();

        try {
            $this->commandBus->handle(new SaveOAuth($code, new UnitId($unitId)));
        } catch (InvalidArgumentException) {
            $this->flashMessage('Nepodařilo se propojit Google účet!', 'danger');
            $this->redirect(':Settings:Mails:default', ['unitId' => $unitId]);
        }

        $this->flashMessage('Propojení s Google účtem proběhlo úspěšně!');
        $this->redirect(':Settings:Mails:default', ['unitId' => $unitId]);
    }
}
