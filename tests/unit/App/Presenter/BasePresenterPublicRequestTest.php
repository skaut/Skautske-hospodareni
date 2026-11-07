<?php

declare(strict_types=1);

namespace App\Presenter;

use App\BasePresenter;
use Codeception\Test\Unit;
use ReflectionMethod;

final class BasePresenterPublicRequestTest extends Unit
{
    /** A user without an active role must still reach the logout pages, so they skip the role sync. */
    public function testLogoutPagesArePublic(): void
    {
        self::assertTrue($this->isPublicRequest('Auth', 'logoutSIS'));
        self::assertTrue($this->isPublicRequest('Auth', 'skautisLogout'));
    }

    public function testAppPageIsNotPublic(): void
    {
        self::assertFalse($this->isPublicRequest('Cashbook', 'default'));
    }

    private function isPublicRequest(string $presenterName, string $action): bool
    {
        $presenter = new class extends BasePresenter {
        };
        $presenter->setParent(null, $presenterName);
        $presenter->changeAction($action);

        $method = new ReflectionMethod($presenter, 'isPublicRequest');
        $method->setAccessible(true);

        return $method->invoke($presenter);
    }
}
