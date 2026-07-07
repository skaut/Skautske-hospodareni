<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class SessionKeepAlivePresenter extends BasePresenter
{
    public function actionDefault(): void
    {
        if (! $this->getUser()->isLoggedIn()) {
            $this->getHttpResponse()->setCode(401);
            $this->sendJson(['ok' => false]);
        }

        try {
            $this->userService->updateLogoutTime();
        } catch (Throwable) {
            $this->getHttpResponse()->setCode(409);
            $this->sendJson(['ok' => false]);
        }

        $this->sendJson(['ok' => true]);
    }
}
