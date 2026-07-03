<?php

declare(strict_types=1);

namespace App\Model\Bank\Fio;

use FioApi\Download\Downloader;

interface IDownloaderFactory
{
    public function create(string $token): Downloader;
}
