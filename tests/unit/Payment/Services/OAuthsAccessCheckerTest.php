<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Codeception\Test\Unit;
use Mockery;
use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\UnitResolverStub;

final class OAuthsAccessCheckerTest extends Unit
{
    private const MAIL_CREDENTIALS_ID = 1;

    /** @var UnitResolverStub */
    private $unitResolver;

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
                ->allUnitsHaveAccessToOAuth([100, 12], self::MAIL_CREDENTIALS_ID)
        );
    }

    public function testAtLeastOneChildUnitOfOwnerUnitGivesAccess() : void
    {
        $this->assertTrue(
            $this->createChecker(20)
                ->allUnitsHaveAccessToOAuth([10, 12], self::MAIL_CREDENTIALS_ID)
        );
    }

    public function testDifferentUnitHasNoAccess() : void
    {
        $this->assertFalse(
            $this->createChecker(10)
                ->allUnitsHaveAccessToOAuth([12], self::MAIL_CREDENTIALS_ID)
        );
    }

    private function createChecker(int $mailCredentialsOwnerUnitId) : OAuthsAccessChecker
    {
        $repository = Mockery::mock(IGoogleRepository::class);
        $repository
            ->shouldReceive('find')
            ->once()
            ->withArgs([self::MAIL_CREDENTIALS_ID])
            ->andReturn(
                OAuth::create(new UnitId($mailCredentialsOwnerUnitId), 'code', 'foo@gmail.com')
            );

        return new OAuthsAccessChecker($repository, $this->unitResolver);
    }
}
