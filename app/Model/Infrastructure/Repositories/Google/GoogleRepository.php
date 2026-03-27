<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Mail;

use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\OAuthId;
use App\Model\Mail\Repositories\IGoogleRepository;
use Doctrine\ORM\EntityManager;

use function array_fill_keys;
use function assert;
use function count;

final class GoogleRepository implements IGoogleRepository
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    public function save(GoogleOAuth $oAuth): void
    {
        $this->entityManager->persist($oAuth);
        $this->entityManager->flush();
    }

    /** @throws OAuthNotFound */
    public function find(OAuthId $oAuthId): GoogleOAuth
    {
        $oAuth = $this->entityManager->getRepository(GoogleOAuth::class)->find($oAuthId);

        if ($oAuth === null) {
            throw new OAuthNotFound();
        }

        return $oAuth;
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, GoogleOAuth[]>
     */
    public function findByUnits(array $unitIds): array
    {
        if (count($unitIds) === 0) {
            return [];
        }

        $byUnit = array_fill_keys($unitIds, []);

        $oAuthList = $this->entityManager->createQuery(<<<'DQL'
            SELECT o FROM App\Model\Google\Entity\GoogleOAuth o WHERE o.unitId IN (:ids) ORDER BY o.email 
        DQL)
            ->setParameter('ids', $unitIds)
            ->getResult();

        foreach ($oAuthList as $oAuth) {
            assert($oAuth instanceof GoogleOAuth);

            $byUnit[$oAuth->getUnitId()->toInt()][] = $oAuth;
        }

        return $byUnit;
    }

    public function findByUnitAndEmail(UnitId $unitId, string $email): GoogleOAuth
    {
        $oAuth = $this->entityManager->createQuery(<<<'DQL'
            SELECT o FROM App\Model\Google\Entity\GoogleOAuth o WHERE o.unitId = :unitId AND o.email = :email
        DQL)
            ->setParameter('unitId', $unitId->toInt())
            ->setParameter('email', $email)
            ->getResult();

        if ($oAuth === []) {
            throw new OAuthNotFound();
        }

        return $oAuth[0];
    }

    public function remove(GoogleOAuth $oAuth): void
    {
        $this->entityManager->remove($oAuth);
        $this->entityManager->flush();
    }
}
