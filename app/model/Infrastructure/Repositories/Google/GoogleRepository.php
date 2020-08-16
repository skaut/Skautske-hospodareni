<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Mail;

use Doctrine\ORM\EntityManager;
use Google_Client;
use Google_Service_Gmail;
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
    private string $credentialsPath;
    private string $tokenUri;
    private EntityManager $entityManager;


    private ?Google_Client $client;

    public function __construct(string $credentialsPath, string $tokenUri, EntityManager $em)
    {
        $this->credentialsPath = $credentialsPath;
        $this->tokenUri        = $tokenUri;
        $this->entityManager   = $em;
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

    /** @return OAuth[] */
    public function findByUnit(UnitId $unitId) : array
    {
        return $this->entityManager->getRepository(OAuth::class)->findBy(['unitId' => $unitId]);
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

        $oAuthList = $this->entityManager->getRepository(OAuth::class)->findBy(['unitId IN' => $unitIds]);

        foreach ($oAuthList as $oAuth) {
            assert($oAuth instanceof OAuth);

            $byUnit[$oAuth->getUnitId()->toInt()][] = $oAuth;
        }

        return $byUnit;
    }

    public function findByUnitAndEmail(UnitId $unitId, string $email) : OAuth
    {
        $oAuth = $this->entityManager->getRepository(OAuth::class)->findOneBy(['unitId' => $unitId, 'email' => $email]);

        if ($oAuth === null) {
            throw new OAuthNotFound();
        }

        return $oAuth;
    }

    public function remove(OAuth $oAuth) : void
    {
        $this->entityManager->remove($oAuth);
        $this->entityManager->flush();
    }

    public function getClient() : Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Skautské hospodaření online');
        $client->setScopes([
            Google_Service_Gmail::GMAIL_SEND,
        ]);
        $client->setAuthConfig($this->credentialsPath);
        $client->setRedirectUri($this->tokenUri);
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setPrompt('select_account consent');

        return $client;
    }
}
