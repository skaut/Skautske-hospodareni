<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Doctrine\ORM\EntityManager;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;
use function array_fill_keys;
use function assert;
use function count;

final class MailCredentialsRepository implements IMailCredentialsRepository
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(int $id) : MailCredentials
    {
        $credentials = $this->entityManager->find(MailCredentials::class, $id);

        if ($credentials === null) {
            throw new MailCredentialsNotFound();
        }

        return $credentials;
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, MailCredentials[]>
     */
    public function findByUnits(array $unitIds) : array
    {
        if (count($unitIds) === 0) {
            return [];
        }

        $byUnit = array_fill_keys($unitIds, []);

        $credentialsList = $this->entityManager->getRepository(MailCredentials::class)->findBy(['unitId IN' => $unitIds]);

        foreach ($credentialsList as $credentials) {
            assert($credentials instanceof MailCredentials);

            $byUnit[$credentials->getUnitId()][] = $credentials;
        }

        return $byUnit;
    }

    public function remove(MailCredentials $credentials) : void
    {
        $this->entityManager->remove($credentials);
        $this->entityManager->flush();
    }

    public function save(MailCredentials $credentials) : void
    {
        $this->entityManager->persist($credentials);
        $this->entityManager->flush();
    }
}
