<?php

declare(strict_types=1);

namespace Model\Bank\Fio;

use FioApi\Downloader;

interface IDownloaderFactory
{
    public function create(string $token) : Downloader;
}
