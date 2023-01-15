<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use InvalidArgumentException;
use Model\Common\UnitId;
use Model\Google\Commands\SaveOAuth;
use Model\Google\ReadModel\Queries\OAuthUrlQuery;

class GooglePresenter extends BasePresenter
{
    public function actionOAuth(): void
    {
        $this->redirectUrl($this->queryBus->handle(new OAuthUrlQuery()));
    }

    public function actionToken(string $code): void
    {
        try {
            $this->commandBus->handle(new SaveOAuth($code, new UnitId($this->userService->getActualRole()->getUnitId())));
        } catch (InvalidArgumentException) {
            $this->flashMessage('Nepodařilo se propojit Google účet!', 'danger');
            $this->redirect(':Accountancy:Payment:Mail:');
        }

        $this->flashMessage('Propojení s Google účtem proběhlo úspěšně!');
        $this->redirect(':Accountancy:Payment:Mail:');
    }
}
