<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Mail;

use Doctrine\ORM\EntityManager;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Oauth2;
use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Google\OAuthNotFound;
use Model\Mail\Repositories\IGoogleRepository;
use function array_fill_keys;
use function assert;
use function count;
use function dump;

final class GoogleRepository implements IGoogleRepository
{
    private string $credentialsPath;
    private string $tokenUri;
    private EntityManager $entityManager;

    public function __construct(string $credentialsPath, string $tokenUri, EntityManager $em)
    {
        $this->credentialsPath = $credentialsPath;
        $this->tokenUri        = $tokenUri;
        $this->entityManager   = $em;
    }

    public function getAuthUrl() : string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function saveAuthCode(string $code, UnitId $unitId) : void
    {
        $client = $this->getClient();
        $token  = $client->fetchAccessTokenWithAuthCode($code);
        $client->setAccessToken($token);
        $service = new Google_Service_Oauth2($client);
        $email   = $service->userinfo->get()->getEmail();

        /** @var OAuth|null $oauth */
        $oauth = $this->entityManager->getRepository(OAuth::class)->findOneBy(['unitId' => $unitId, 'email' => $email]);
        if ($oauth !== null) {
            $oauth->setToken($token['refresh_token']);
        } else {
            $oauth = OAuth::create($unitId, $code, $email);
        }
        $this->entityManager->persist($oauth);
        $this->entityManager->flush();
    }

    /**
     * @throws OAuthNotFound
     */
    public function find(OAuthId $oAuthId) : ?OAuth
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
     * @return OAuth[]
     */
    public function findByUnits(array $unitIds) : array
    {
        if (count($unitIds) === 0) {
            return [];
        }

        $byUnit = array_fill_keys($unitIds, []);

        $credentialsList = $this->entityManager->getRepository(OAuth::class)->findBy(['unitId IN' => $unitIds]);

        foreach ($credentialsList as $credentials) {
            assert($credentials instanceof OAuth);

            $byUnit[$credentials->getUnitId()->toInt()][] = $credentials;
        }

        return $byUnit;
    }

    public function remove(OAuth $oAuth) : void
    {
        $this->entityManager->remove($oAuth);
        $this->entityManager->flush();
    }

    public function getGmailService(OAuth $oAuth) : Google_Service_Gmail
    {
        $client = $this->getClient();
        $token  = $client->fetchAccessTokenWithRefreshToken($oAuth->getToken());
        $client->setAccessToken($token);
        dump($client->isAccessTokenExpired());

        return new Google_Service_Gmail($client);
    }

    private function getClient() : Google_Client
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
