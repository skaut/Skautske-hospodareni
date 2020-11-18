<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Codeception\Test\Unit;
use Mockery;
use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\UnitResolverStub;

final class OAuthsAccessCheckerTest extends Unit
{
    /** @var UnitResolverStub */
    private $unitResolver;

    private OAuthId $oAuthId;

    protected function setUp() : void
    {
        parent::setUp();
        $this->unitResolver = new UnitResolverStub();
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            11 => 20,
            12 => 30,
        ]);
        $this->oAuthId = OAuthId::generate();
    }

    public function testAtLeastOneUnitSameAsOwnerUnitGivesAccess() : void
    {
        $this->assertTrue(
            $this->createChecker(100)
                ->allUnitsHaveAccessToOAuth([100, 12], $this->oAuthId)
        );
    }

    public function testAtLeastOneChildUnitOfOwnerUnitGivesAccess() : void
    {
        $this->assertTrue(
            $this->createChecker(20)
                ->allUnitsHaveAccessToOAuth([10, 12], $this->oAuthId)
        );
    }

    public function testDifferentUnitHasNoAccess() : void
    {
        $this->assertFalse(
            $this->createChecker(10)
                ->allUnitsHaveAccessToOAuth([12], $this->oAuthId)
        );
    }

    private function createChecker(int $oAuthOwnerUnitId) : OAuthsAccessChecker
    {
        $repository = Mockery::mock(IGoogleRepository::class);
        $repository
            ->shouldReceive('find')
            ->once()
            ->withArgs([$this->oAuthId])
            ->andReturn(
                OAuth::create(new UnitId($oAuthOwnerUnitId), 'code', 'foo@gmail.com')
            );

        return new OAuthsAccessChecker($repository, $this->unitResolver);
    }
}
