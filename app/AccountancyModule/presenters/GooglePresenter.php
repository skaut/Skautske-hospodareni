<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use InvalidArgumentException;
use Model\Common\UnitId;
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
        $this->redirectUrl($this->googleRepository->getAuthUrl());
    }

    public function actionToken(string $code) : void
    {
        try {
            $this->googleRepository->saveAuthCode($code, new UnitId($this->userService->getActualRole()->getUnitId()));
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage('Neplatná Google autorizace!', 'danger');
            $this->redirect(':Accountancy:Payment:Mail:');
        }
        $this->flashMessage('Autorizace na Google proběhla úspěšně!');
        $this->redirect(':Accountancy:Payment:Mail:');
    }
}
