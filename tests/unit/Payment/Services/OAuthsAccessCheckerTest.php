<?php

declare(strict_types=1);

namespace App\Model\Payment\Services;

use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\OAuthId;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\UnitResolverStub;
use Codeception\Test\Unit;
use Mockery;

final class OAuthsAccessCheckerTest extends Unit
{
    private UnitResolverStub $unitResolver;

    private OAuthId $oAuthId;

    protected function setUp(): void
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

    public function testAtLeastOneUnitSameAsOwnerUnitGivesAccess(): void
    {
        $this->assertTrue(
            $this->createChecker(100)
                ->allUnitsHaveAccessToOAuth([100, 12], $this->oAuthId),
        );
    }

    public function testAtLeastOneChildUnitOfOwnerUnitGivesAccess(): void
    {
        $this->assertTrue(
            $this->createChecker(20)
                ->allUnitsHaveAccessToOAuth([10, 12], $this->oAuthId),
        );
    }

    public function testDifferentUnitHasNoAccess(): void
    {
        $this->assertFalse(
            $this->createChecker(10)
                ->allUnitsHaveAccessToOAuth([12], $this->oAuthId),
        );
    }

    private function createChecker(int $oAuthOwnerUnitId): OAuthsAccessChecker
    {
        $repository = Mockery::mock(IGoogleRepository::class);
        $repository
            ->shouldReceive('find')
            ->once()
            ->withArgs([$this->oAuthId])
            ->andReturn(
                GoogleOAuth::create(new UnitId($oAuthOwnerUnitId), 'code', 'foo@gmail.com'),
            );

        return new OAuthsAccessChecker($repository, $this->unitResolver);
    }
}
