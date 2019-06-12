<?php

declare(strict_types=1);

namespace Model\Common;

use Model\Common\Exception\InvalidScanFile;

interface IScanStorage
{
    public const ALLOWED_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
    ];

    /**
     * @throws InvalidScanFile
     */
    public function save(string $path, string $contents) : void;

    /**
     * @throws FileNotFound
     */
    public function get(string $path) : File;

    public function delete(string $path) : void;
}
