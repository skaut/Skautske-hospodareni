<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use DateTimeImmutable;
use eGen\MessageBus\Bus\EventBus;
use IntegrationTest;
use Model\Google\OAuthNotFound;
use Model\Infrastructure\Repositories\Mail\GoogleRepository;
use Model\Payment\DomainEvents\OAuthWasRemoved;
use Model\Payment\MailCredentials;
use function array_keys;

class MailCredentialsRepositoryTest extends IntegrationTest
{
    /** @var GoogleRepository */
    private $repository;

    /** @var EventBus */
    private $eventBus;

    /**
     * @return string[]
     */
    public function getTestedAggregateRoots() : array
    {
        return [MailCredentials::class];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new GoogleRepository('', '', $this->entityManager);
    }

    public function testFind() : void
    {
        $row = [
            'host' => 'smtp.gmail.com',
            'username' => 'mail',
            'password' => 'pass123',
            'unitId' => 666,
            'secure' => 'ssl',
            'sender' => 'test@seznam.cz',
            'created' => '2017-01-01 00:00:00',
        ];

        $this->tester->haveInDatabase('pa_smtp', $row);

        $credentials = $this->repository->find(1);

        $this->assertSame($row['host'], $credentials->getHost());
        $this->assertSame($row['username'], $credentials->getUsername());
        $this->assertSame($row['password'], $credentials->getPassword());
        $this->assertSame($row['unitId'], $credentials->getUnitId());
        $this->assertSame(MailCredentials\MailProtocol::get(MailCredentials\MailProtocol::SSL), $credentials->getProtocol());
        $this->assertSame($row['sender'], $credentials->getSender());
        $this->assertEquals(new DateTimeImmutable($row['created']), $credentials->getCreatedAt());
    }

    public function testFindNotExistingConfigThrowsException() : void
    {
        $this->expectException(OAuthNotFound::class);

        $this->repository->find(19);
    }

    public function testFindByUnits() : void
    {
        $rows   = [];
        $rows[] = [
            'host' => 'smtp.gmail.com',
            'username' => 'mail',
            'password' => 'pass123',
            'unitId' => 666,
            'secure' => 'ssl',
            'sender' => 'test@seznam.cz',
            'created' => '2017-01-01 00:00:00',
        ];

        $rows[] = [
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 663,
            'secure' => 'tls',
            'sender' => 'test@seznam.cz',
            'created' => '2017-01-01 00:00:00',
        ];

        $this->tester->haveInDatabase('pa_smtp', $rows[0]);
        $this->tester->haveInDatabase('pa_smtp', [
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 626,
            'secure' => 'tls',
            'sender' => 'test@seznam.cz',
            'created' => '2017-01-01 00:00:00',
        ]);
        $this->tester->haveInDatabase('pa_smtp', $rows[1]);

        $protocols = [
            'ssl' => MailCredentials\MailProtocol::get(MailCredentials\MailProtocol::SSL),
            'tls' => MailCredentials\MailProtocol::get(MailCredentials\MailProtocol::TLS),
        ];

        $unitIds = [663, 666];

        $credentialsListByUnit = $this->repository->findByUnits($unitIds);

        $this->assertSame($unitIds, array_keys($credentialsListByUnit));

        foreach ([$credentialsListByUnit[666][0], $credentialsListByUnit[663][0]] as $index => $credentials) {
            $row = $rows[$index];
            $this->assertSame($row['host'], $credentials->getHost());
            $this->assertSame($row['username'], $credentials->getUsername());
            $this->assertSame($row['password'], $credentials->getPassword());
            $this->assertSame($row['unitId'], $credentials->getUnitId());
            $this->assertSame($protocols[$row['secure']], $credentials->getProtocol());
            $this->assertSame($row['sender'], $credentials->getSender());
            $this->assertEquals(new DateTimeImmutable($row['created']), $credentials->getCreatedAt());
        }
    }

    public function testRemove() : void
    {
        $this->eventBus->shouldReceive('handle')
            ->once()
            ->withArgs(static function (OAuthWasRemoved $event) : bool {
                return $event->getOAuthId() === 1;
            });

        $this->tester->haveInDatabase('pa_smtp', [
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 663,
            'secure' => 'tls',
            'sender' => 'test@seznam.cz',
            'created' => '2017-01-01 00:00:00',
        ]);

        $credentials = $this->repository->find(1);

        $this->repository->remove($credentials);

        $this->tester->dontSeeInDatabase('pa_smtp', ['id' => 1]);
    }

    public function testSave() : void
    {
        $credentials = new MailCredentials(
            663,
            'smtp.seznam.cz',
            'mail2',
            'pass',
            MailCredentials\MailProtocol::get(MailCredentials\MailProtocol::TLS),
            'test@seznam.cz',
            new DateTimeImmutable('2017-01-01 00:00:00')
        );

        $this->repository->save($credentials);

        $this->tester->seeInDatabase('pa_smtp', [
            'id' => 1,
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 663,
            'secure' => 'tls',
            'sender' => 'test@seznam.cz',
            'created' => '2017-01-01 00:00:00',
        ]);
    }

    public function findByUnitsWithEmptyIdsReturnsEmptyArray() : void
    {
        $this->assertSame([], $this->repository->findByUnits([]));
    }
}
