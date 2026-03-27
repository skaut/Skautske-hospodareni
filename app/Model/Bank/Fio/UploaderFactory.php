<?php

declare(strict_types=1);

namespace App\Model\Bank\Fio;

use FioApi\Upload\Uploader;
use GuzzleHttp\ClientInterface;

final class UploaderFactory implements IUploaderFactory
{
    public function __construct(private ClientInterface $client)
    {
    }

    public function create(string $token, string $accountFrom): Uploader
    {
        return new Uploader($token, $accountFrom, $this->client);
    }
}
