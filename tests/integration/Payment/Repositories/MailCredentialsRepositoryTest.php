<?php

namespace Model\Payment\Repositories;

use Doctrine\ORM\EntityManager;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;

class MailCredentialsRepositoryTest extends \IntegrationTest
{

    /** @var EntityManager */
    private $entityManager;

    /** @var MailCredentialsRepository */
    private $repository;

    public function getTestedEntites(): array
    {
        return [
            MailCredentials::class,
        ];
    }

    protected function _before()
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->repository = new MailCredentialsRepository($this->entityManager);
    }

    public function testFind()
    {
        $row = [
            'host' => 'smtp.gmail.com',
            'username' => 'mail',
            'password' => 'pass123',
            'unitId' => 666,
            'secure' => 'ssl',
            'created' => '2017-01-01 00:00:00',
        ];

        $this->tester->haveInDatabase('pa_smtp', $row);

        $credentials = $this->repository->find(1);

        $this->assertSame($row['host'], $credentials->getHost());
        $this->assertSame($row['username'], $credentials->getUsername());
        $this->assertSame($row['password'], $credentials->getPassword());
        $this->assertSame($row['unitId'], $credentials->getUnitId());
        $this->assertSame(MailCredentials\MailProtocol::get(MailCredentials\MailProtocol::SSL), $credentials->getProtocol());
        $this->assertEquals(new \DateTimeImmutable($row['created']), $credentials->getCreatedAt());
    }

    public function testFindNotExistingConfigThrowsException()
    {
        $this->expectException(MailCredentialsNotFound::class);

        $this->repository->find(19);
    }

    public function testFindByUnits()
    {
        $rows = [];
        $rows[] = [
            'host' => 'smtp.gmail.com',
            'username' => 'mail',
            'password' => 'pass123',
            'unitId' => 666,
            'secure' => 'ssl',
            'created' => '2017-01-01 00:00:00',
        ];

        $rows[] = [
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 663,
            'secure' => 'tls',
            'created' => '2017-01-01 00:00:00',
        ];

        $this->tester->haveInDatabase('pa_smtp', $rows[0]);
        $this->tester->haveInDatabase('pa_smtp', [
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 626,
            'secure' => 'tls',
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

        foreach([$credentialsListByUnit[666][0], $credentialsListByUnit[663][0]] as $index => $credentials) {
            $row = $rows[$index];
            $this->assertSame($row['host'], $credentials->getHost());
            $this->assertSame($row['username'], $credentials->getUsername());
            $this->assertSame($row['password'], $credentials->getPassword());
            $this->assertSame($row['unitId'], $credentials->getUnitId());
            $this->assertSame($protocols[$row['secure']], $credentials->getProtocol());
            $this->assertEquals(new \DateTimeImmutable($row['created']), $credentials->getCreatedAt());
        }
    }

    public function testRemove()
    {
        $this->tester->haveInDatabase('pa_smtp', [
            'host' => 'smtp.seznam.cz',
            'username' => 'mail2',
            'password' => 'pass',
            'unitId' => 663,
            'secure' => 'tls',
            'created' => '2017-01-01 00:00:00',
        ]);

        $credentials = $this->repository->find(1);

        $this->repository->remove($credentials);

        $this->tester->dontSeeInDatabase('pa_smtp', ['id' => 1]);
    }

    public function findByUnitsWithEmptyIdsReturnsEmptyArray()
    {
        $this->assertSame([], $this->repository->findByUnits([]));
    }

}
