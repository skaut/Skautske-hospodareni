<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use InvalidArgumentException;
use Model\Mail\Repositories\IGoogleRepository;

class GooglePresenter extends BasePresenter
{
    private IGoogleRepository $googleRepository;

    public function __construct(IGoogleRepository $mailRepository)
    {
        $this->mailRepository = $mailRepository;
    }

    public function actionOAuth() : void
    {
        $this->redirectUrl($this->googleRepository->getAuthUrl());
    }

    public function actionToken(string $code) : void
    {
        try {
            $this->mailRepository->saveAuthCode($code, $this->userService->getActualRole()->getUnitId());
        } catch (InvalidArgumentException $exc) {
            $this->flashMessage('Neplatná Google autorizace!', 'danger');
            $this->redirect('this');
        }
        $this->flashMessage('Autorizace na Google proběhla úspěšně!');
        $this->redirect('Default');
    }
}
