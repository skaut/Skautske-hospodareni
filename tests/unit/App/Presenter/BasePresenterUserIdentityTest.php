<?php

declare(strict_types=1);

namespace App\Presenter;

use App\BasePresenter;
use Codeception\Test\Unit;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use ReflectionMethod;

final class BasePresenterUserIdentityTest extends Unit
{
    public function testNullUserIdentityIsInvalid(): void
    {
        self::assertFalse($this->isValidUserIdentity(null));
    }

    public function testUserIdentityWithoutNumericIdIsInvalid(): void
    {
        self::assertFalse($this->isValidUserIdentity(new SimpleIdentity('')));
    }

    public function testUserIdentityWithNumericIdIsValid(): void
    {
        self::assertTrue($this->isValidUserIdentity(new SimpleIdentity(123)));
    }

    private function isValidUserIdentity(?IIdentity $identity): bool
    {
        $presenter = new class extends BasePresenter {
        };

        $method = new ReflectionMethod($presenter, 'isValidUserIdentity');
        $method->setAccessible(true);

        return $method->invoke($presenter, $identity);
    }
}
