<?php


namespace Model\Bank\Fio;


use FioApi\Downloader;
use GuzzleHttp\ClientInterface;

final class DownloaderFactory implements IDownloaderFactory
{

    /** @var ClientInterface */
    private $client;


    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function create(string $token): Downloader
    {
        return new Downloader($token, $this->client);
    }

}
