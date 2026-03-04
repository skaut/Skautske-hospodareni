<?php

declare(strict_types=1);

namespace Model\Bank\Fio;

use FioApi\Upload\Uploader;

interface IUploaderFactory
{
    public function create(string $token, string $accountFrom): Uploader;
}
