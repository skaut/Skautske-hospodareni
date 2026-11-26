<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\QueryHandlers;

use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Google\BrokenSenderAccount;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\OAuthId;
use App\Model\Google\ReadModel\Queries\BrokenSenderAccountsQuery;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\Unit;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\ReadModel\Queries\EditableUnitsQuery;
use App\Model\User\SkautisRole;
use Codeception\Test\Unit as TestCase;
use DateTimeImmutable;
use Mockery;

use function array_fill_keys;
use function array_map;

/** Only a role of the account's own unit that may edit it can connect a refused account again. */
final class BrokenSenderAccountsQueryHandlerTest extends TestCase
{
    private const ROLE_UNIT = 27266;
    private const OTHER_UNIT = 24404;

    protected function _after(): void
    {
        Mockery::close();
    }

    public function testRoleOfTheAccountsUnitCanConnectItAgain(): void
    {
        $account = $this->account(self::ROLE_UNIT, invalid: true);

        $broken = $this->handle([$account], $this->role(self::ROLE_UNIT), editableUnits: [self::ROLE_UNIT]);

        self::assertCount(1, $broken);
        self::assertSame('sender@skaut.cz', $broken[0]->email);
        self::assertTrue($broken[0]->canReconnect);
    }

    /** E.g. a role of a parent unit may edit the unit of the account, but connecting goes to its own unit. */
    public function testRoleOfAnotherUnitIsToldWhoHasToFixIt(): void
    {
        $account = $this->account(self::OTHER_UNIT, invalid: true);

        $broken = $this->handle([$account], $this->role(self::ROLE_UNIT), editableUnits: [self::ROLE_UNIT, self::OTHER_UNIT]);

        self::assertFalse($broken[0]->canReconnect);
        self::assertSame('Středisko '.self::OTHER_UNIT, $broken[0]->unitName);
    }

    public function testRoleThatCannotEditTheUnitCannotConnectIt(): void
    {
        $account = $this->account(self::ROLE_UNIT, invalid: true);

        $broken = $this->handle([$account], $this->role(self::ROLE_UNIT), editableUnits: []);

        self::assertFalse($broken[0]->canReconnect);
    }

    public function testUserWithoutRoleCannotConnectIt(): void
    {
        $account = $this->account(self::ROLE_UNIT, invalid: true);

        $broken = $this->handle([$account], null, editableUnits: []);

        self::assertFalse($broken[0]->canReconnect);
    }

    /** A sender of more groups is reported once, a working one not at all. */
    public function testOnlyRefusedAccountsAreReportedOnce(): void
    {
        $refused = $this->account(self::ROLE_UNIT, invalid: true);
        $working = $this->account(self::ROLE_UNIT, invalid: false);

        $broken = $this->handle([$refused, $working, $refused], $this->role(self::ROLE_UNIT), editableUnits: [self::ROLE_UNIT]);

        self::assertSame([$refused->getId()->toString()], array_map(static fn (BrokenSenderAccount $account): string => $account->oAuthId, $broken));
    }

    /**
     * @param list<GoogleOAuth> $accounts
     * @param list<int>         $editableUnits
     *
     * @return list<BrokenSenderAccount>
     */
    private function handle(array $accounts, ?SkautisRole $role, array $editableUnits): array
    {
        $repository = Mockery::mock(IGoogleRepository::class);
        foreach ($accounts as $account) {
            $repository->shouldReceive('find')->with($account->getId())->andReturn($account);
        }

        $queryBus = Mockery::mock(QueryBus::class);
        $roleExpectation = $queryBus->shouldReceive('handle')->with(Mockery::type(ActiveSkautisRoleQuery::class));
        $role === null ? $roleExpectation->andThrow(new MissingCurrentRole()) : $roleExpectation->andReturn($role);
        $queryBus->shouldReceive('handle')->with(Mockery::type(EditableUnitsQuery::class))->andReturn(array_fill_keys($editableUnits, 'unit'));
        $queryBus->shouldReceive('handle')->with(Mockery::type(UnitQuery::class))->andReturnUsing(
            static fn (UnitQuery $query): Unit => new Unit($query->getUnitId(), '', 'Středisko '.$query->getUnitId(), null, '', '', '', '', 'stredisko'),
        );

        $handler = new BrokenSenderAccountsQueryHandler($repository, $queryBus);

        return $handler(new BrokenSenderAccountsQuery(array_map(static fn (GoogleOAuth $account): OAuthId => $account->getId(), $accounts)));
    }

    private function account(int $unitId, bool $invalid): GoogleOAuth
    {
        $account = Mockery::mock(GoogleOAuth::class);
        $account->shouldReceive([
            'getId' => OAuthId::generate(),
            'getEmail' => 'sender@skaut.cz',
            'getUnitId' => new UnitId($unitId),
            'isInvalid' => $invalid,
            'getInvalidSince' => $invalid ? new DateTimeImmutable('2026-11-09 10:00:00') : null,
        ]);

        return $account;
    }

    private function role(int $unitId): SkautisRole
    {
        return new SkautisRole('vedouciStredisko', 'Vedoucí', $unitId, 'Středisko');
    }
}
