<?php

declare(strict_types=1);

namespace Utility\Ares;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use RuntimeException;
use Throwable;
use ValueError;

use function ctype_alpha;
use function is_array;
use function is_null;
use function preg_replace;
use function sprintf;
use function strlen;
use function strtoupper;
use function strval;
use function substr;
use function trim;

class ViAresParser
{
    private const COUNTRY_CODE_CZ = 'CZ';

    public function __construct(
        private readonly ClientInterface $client = new Client(),
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getViAresInfo(string $vat): ViAresInfo
    {
        [$countryCode, $vat] = $this->splitAndValidate($vat);
        if ($countryCode === self::COUNTRY_CODE_CZ) {
            return $this->getAres($vat);
        }

        return $this->getVies($vat, $countryCode);
    }

    /**
     * Return info from Ares.cz.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getAres(string $vat): ViAresInfo
    {
        $companyNumber = $this->normalizeCompanyNumber($vat);
        $url = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/'.$companyNumber;

        $response = $this->client->request('GET', $url);
        $responseData = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);

        if (! is_array($responseData) || ! isset($responseData['ico']) || strval($responseData['ico']) !== $companyNumber) {
            return new ViAresInfo();
        }

        $address = isset($responseData['sidlo']) && is_array($responseData['sidlo'])
            ? $responseData['sidlo']
            : [];

        $aresInfo = new ViAresInfo();
        $aresInfo->setCompanyName(strval($responseData['ico']))
            ->setName(isset($responseData['obchodniJmeno']) ? strval($responseData['obchodniJmeno']) : null)
            ->setStreet(isset($address['nazevUlice']) ? strval($address['nazevUlice']) : null)
            ->setCity(isset($address['nazevObce']) ? strval($address['nazevObce']) : null)
            ->setZipCode(isset($address['psc']) ? strval($address['psc']) : null)
            ->setStreetNumber(isset($address['cisloDomovni']) ? strval($address['cisloDomovni']) : null)
            ->setStreetNumberSuffix(isset($address['cisloOrientacni']) ? strval($address['cisloOrientacni']) : null)
            ->setVatPayer(isset($responseData['dic']))
            ->setVat(isset($responseData['dic']) ? strval($responseData['dic']) : null);

        return $aresInfo;
    }

    /**
     * return info from ec.europa.eu.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getVies(string $vat, string $countryCode): ViAresInfo
    {
        $url = sprintf('https://ec.europa.eu/taxation_customs/vies/rest-api/ms/%s/vat/%s', $countryCode, $vat);

        $header = [
            'name' => ['name'],
            'address' => ['address'],
            'vat' => ['vatNumber'],
        ];

        try {
            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Response error code :'.$response->getStatusCode());
            }

            $data = Json::decode($response->getBody()->getContents());

            if (! isset($data->isValid) || ! $data->isValid) {
                throw new ValueError('Data from VIES are not valid.');
            }

            $rowValues = $this->processResponse($header, $data);
        } catch (Throwable $e) {
            throw $e;
        }

        return new ViAresInfo([
            'vat' => $rowValues['vat'],
            'name' => $rowValues['name'],
            'address' => $rowValues['address'],
            'vatPayer' => 0,
            'countryCode' => $countryCode,
        ]);
    }

    /** @return array<int, string> */
    private function splitAndValidate(string $inputString): array
    {
        $firstTwoChars = substr($inputString, 0, 2);
        if (! ctype_alpha($firstTwoChars)) {
            throw new ValueError('První dva znaky nejsou písmena.');
        }

        $restOfString = substr($inputString, 2);
        $length = strlen($restOfString);
        if ($length > 7 && $length < 13) {
            return [strtoupper($firstTwoChars), $restOfString];
        }

        throw new ValueError('Počet číslic v IČO je mimo rozsah. Přípustný rozsah je 8 až 12 číslic.');
    }

    /**
     * Zpracuje odpověď z API a připraví data pro výstup.
     *
     * @param array<string, mixed> $header Struktura hlavičky
     * @param object               $data   Data z odpovědi API
     *
     * @return array<string, string|null>
     */
    private function processResponse(array $header, object $data): array
    {
        $rowValues = [];

        foreach ($header as $key => $paths) {
            if (is_null($paths)) {
                $rowValues[$key] = null;
                continue;
            }

            if (! is_array($paths)) {
                $rowValues[$key] = $paths;
                continue;
            }

            $value = null;
            foreach ($paths as $path) {
                if (isset($data->$path)) {
                    $value = $data->$path;
                    break;
                }
            }

            $rowValues[$key] = $value;
        }

        return $rowValues;
    }

    private function normalizeCompanyNumber(string $companyNumber): string
    {
        return trim((string) preg_replace('/\s+/', '', $companyNumber));
    }
}
