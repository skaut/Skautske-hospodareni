<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Mockery;
use Model\Payment\MailCredentials;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Payment\UnitResolverStub;

final class MailCredentialsAccessCheckerTest extends Unit
{
    private const MAIL_CREDENTIALS_ID = 1;

    private UnitResolverStub $unitResolver;

    protected function setUp() : void
    {
        parent::setUp();
        $this->unitResolver = new UnitResolverStub();
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            11 => 20,
            12 => 30,
        ]);
    }

    public function testAtLeastOneUnitSameAsOwnerUnitGivesAccess() : void
    {
        $this->assertTrue(
            $this->createChecker(100)
                ->allUnitsHaveAccessToMailCredentials([100, 12], self::MAIL_CREDENTIALS_ID)
        );
    }

    public function testAtLeastOneChildUnitOfOwnerUnitGivesAccess() : void
    {
        $this->assertTrue(
            $this->createChecker(20)
                ->allUnitsHaveAccessToMailCredentials([10, 12], self::MAIL_CREDENTIALS_ID)
        );
    }

    public function testDifferentUnitHasNoAccess() : void
    {
        $this->assertFalse(
            $this->createChecker(10)
                ->allUnitsHaveAccessToMailCredentials([12], self::MAIL_CREDENTIALS_ID)
        );
    }

    private function createChecker(int $mailCredentialsOwnerUnitId) : MailCredentialsAccessChecker
    {
        $repository = Mockery::mock(IMailCredentialsRepository::class);
        $repository
            ->shouldReceive('find')
            ->once()
            ->withArgs([self::MAIL_CREDENTIALS_ID])
            ->andReturn(
                new MailCredentials(
                    $mailCredentialsOwnerUnitId,
                    'foo@gmail.com',
                    'foo',
                    'bar',
                    MailCredentials\MailProtocol::TLS(),
                    'foo@gmail.com',
                    new DateTimeImmutable()
                )
            );

        return new MailCredentialsAccessChecker($repository, $this->unitResolver);
    }
}
