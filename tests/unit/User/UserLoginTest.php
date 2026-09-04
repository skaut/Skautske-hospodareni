<?php

declare(strict_types=1);

namespace App\Model\User\Entity;

use App\Model\User\DeviceInfo;
use Codeception\Test\Unit as TestCase;
use DateTimeImmutable;
use InvalidArgumentException;

final class UserLoginTest extends TestCase
{
    public function testSessionStartsWithZeroActiveTime(): void
    {
        $login = $this->createLogin(new DateTimeImmutable('2026-08-28 09:00:00'));

        self::assertSame(0, $login->getActiveSecondsSoFar());
        self::assertFalse($login->wasEndedByLogout());
        self::assertNull($login->getLoggedOutAt());
    }

    public function testActiveTimeGrowsWithActivity(): void
    {
        $login = $this->createLogin(new DateTimeImmutable('2026-08-28 09:00:00'));

        $login->touch(new DateTimeImmutable('2026-08-28 09:42:00'));

        self::assertSame(42 * 60, $login->getActiveSecondsSoFar());
    }

    /**
     * Requests can be recorded out of order — a slow one finishing after a
     * faster one — and that must not roll the session back.
     */
    public function testOlderActivityDoesNotRewindTheSession(): void
    {
        $login = $this->createLogin(new DateTimeImmutable('2026-08-28 09:00:00'));

        $login->touch(new DateTimeImmutable('2026-08-28 09:42:00'));
        $login->touch(new DateTimeImmutable('2026-08-28 09:10:00'));

        self::assertSame(42 * 60, $login->getActiveSecondsSoFar());
    }

    public function testLogoutClosesTheSessionAndCountsAsActivity(): void
    {
        $login = $this->createLogin(new DateTimeImmutable('2026-08-28 09:00:00'));

        $login->markLoggedOut(new DateTimeImmutable('2026-08-28 09:30:00'));

        self::assertTrue($login->wasEndedByLogout());
        self::assertSame(UserLogin::END_REASON_LOGOUT, $login->getEndReason());
        self::assertSame(30 * 60, $login->getActiveSecondsSoFar());
    }

    public function testBlankRoleDetailsAreStoredAsNull(): void
    {
        $login = new UserLogin(42, 0, 0, '', $this->createDevice());

        self::assertNull($login->getUnitId());
        self::assertNull($login->getRoleId());
        self::assertNull($login->getRoleKey());
    }

    public function testUserIdMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserLogin(0, 1, 1, 'vedouciStredisko', $this->createDevice());
    }

    private function createLogin(DateTimeImmutable $loggedInAt): UserLogin
    {
        return new UserLogin(42, 100, 7, 'vedouciStredisko', $this->createDevice(), $loggedInAt);
    }

    private function createDevice(): DeviceInfo
    {
        return new DeviceInfo(DeviceInfo::TYPE_DESKTOP, 'Chrome', '130', 'Windows');
    }
}
