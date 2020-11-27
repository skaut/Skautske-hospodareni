<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Mail;

use Doctrine\ORM\EntityManager;
use Model\Common\UnitId;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Mail\Repositories\IGoogleRepository;
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

    public function save(OAuth $oAuth) : void
    {
        $this->entityManager->persist($oAuth);
        $this->entityManager->flush();
    }

    /**
     * @throws OAuthNotFound
     */
    public function find(OAuthId $oAuthId) : OAuth
    {
        $oAuth = $this->entityManager->getRepository(OAuth::class)->find($oAuthId);

        if ($oAuth === null) {
            throw new OAuthNotFound();
        }

        return $oAuth;
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, OAuth[]>
     */
    public function findByUnits(array $unitIds) : array
    {
        if (count($unitIds) === 0) {
            return [];
        }

        $byUnit = array_fill_keys($unitIds, []);

        $oAuthList = $this->entityManager->createQuery(<<<'DQL'
            SELECT o FROM Model\Google\OAuth o WHERE o.unitId IN (:ids) ORDER BY o.email 
        DQL)
            ->setParameter('ids', $unitIds)
            ->getResult();

        foreach ($oAuthList as $oAuth) {
            assert($oAuth instanceof OAuth);

            $byUnit[$oAuth->getUnitId()->toInt()][] = $oAuth;
        }

        return $byUnit;
    }

    public function findByUnitAndEmail(UnitId $unitId, string $email) : OAuth
    {
        $oAuth = $this->entityManager->createQuery(<<<'DQL'
            SELECT o FROM Model\Google\OAuth o WHERE o.unitId = :unitId AND o.email = :email
        DQL)
            ->execute(
                [
                    'unitId' => $unitId->toInt(),
                    'email' => $email,
                ],
            );

        if ($oAuth === []) {
            throw new OAuthNotFound();
        }

        return $oAuth[0];
    }

    public function remove(OAuth $oAuth) : void
    {
        $this->entityManager->remove($oAuth);
        $this->entityManager->flush();
    }
}
