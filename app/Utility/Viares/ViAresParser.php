<?php

declare(strict_types=1);

namespace Utility\Ares;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Throwable;
use ValueError;

use function ctype_alpha;
use function is_array;
use function is_null;
use function sprintf;
use function strlen;
use function strtoupper;
use function strval;
use function substr;

class ViAresParser
{
    private const COUNTRY_CODE_CZ = 'CZ';

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
        $url = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/'.$vat;

        $client = new Client();
        $response = $client->request('GET', $url);
        $response = Json::decode($response->getBody()->getContents());

        if (! isset($response->ico) || $response->ico !== $vat) {
            return new ViAresInfo();
        }

        $aresInfo = new ViAresInfo();
        $aresInfo->setCompanyName(strval($response->ico))
            ->setName(strval($response->obchodniJmeno))
            ->setAddress(strval($response->sidlo->textovaAdresa))
            ->setVatPayer(isset($response->dic))
            ->setVat($response->dic)
            ->setCountryCode(self::COUNTRY_CODE_CZ);

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
            $client = new Client();
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                throw new BadResponseException('Response error code :'.$response->getStatusCode());
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
     * @return array<string, string>
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
}
