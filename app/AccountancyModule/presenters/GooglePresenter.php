<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use InvalidArgumentException;
use Model\Common\UnitId;
use Model\Google\Commands\SaveOAuth;
use Model\Google\ReadModel\Queries\OAuthQuery;
use Model\Mail\Repositories\IGoogleRepository;

class GooglePresenter extends BasePresenter
{
    private IGoogleRepository $googleRepository;

    public function __construct(IGoogleRepository $googleRepository)
    {
        parent::__construct();
        $this->googleRepository = $googleRepository;
    }

    public function actionOAuth() : void
    {
        $this->redirectUrl($this->queryBus->handle(new OAuthQuery()));
    }

    public function actionToken(string $code) : void
    {
        try {
            $this->commandBus->handle(new SaveOAuth($code, new UnitId($this->userService->getActualRole()->getUnitId())));
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage('Nepodařilo se propojit Google účet!', 'danger');
            $this->redirect(':Accountancy:Payment:Mail:');
        }
        $this->flashMessage('Propojení s Google účtem proběhlo úspěšně!');
        $this->redirect(':Accountancy:Payment:Mail:');
    }
}
