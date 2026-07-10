<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use App\Model\Common\Storage\AbstractStorage;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use League\Flysystem\FilesystemOperator;
use Nette\Http\FileUpload;
use Psr\Http\Message\StreamInterface;

use function ltrim;
use function pathinfo;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

use const PATHINFO_EXTENSION;

final class InvoiceImageStorage extends AbstractStorage
{
    public const TYPE_LOGO = 'logo';
    public const TYPE_STAMP = 'stamp';

    public const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function __construct(
        FilesystemOperator $filesystem,
        private string $uploadDirectory,
    ) {
        $this->uploadDirectory = rtrim($uploadDirectory, '/');
        parent::__construct($filesystem, $this->uploadDirectory);
    }

    public function replace(InvoiceUnitSetting $setting, FileUpload $upload, string $type): string
    {
        $this->delete($this->getImagePath($setting, $type));

        $relativePath = $this->buildRelativePath($setting, $upload, $type);
        $this->writeUploadPath($relativePath, $upload);

        return $relativePath;
    }

    public function delete(?string $path): void
    {
        if ($path === null) {
            return;
        }

        $this->deletePath($this->toRelativePath($path));
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $this->existsPath($this->toRelativePath($path));
    }

    public function getStream(string $path): StreamInterface
    {
        return $this->readStreamPath($this->toRelativePath($path));
    }

    public function getReadableLocalPath(string $path): ?string
    {
        $relativePath = $this->toRelativePath($path);
        if (! $this->existsPath($relativePath)) {
            return null;
        }

        return $this->getLocalPath($relativePath) ?? $relativePath;
    }

    public function getContentType(string $path): string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return self::CONTENT_TYPES[$extension] ?? 'application/octet-stream';
    }

    public function getDownloadName(string $type): string
    {
        return $type === self::TYPE_LOGO ? 'logo' : 'razitko-podpis';
    }

    private function buildRelativePath(InvoiceUnitSetting $setting, FileUpload $upload, string $type): string
    {
        $extension = $upload->getContentType() === 'image/png' ? 'png' : 'jpg';

        return sprintf(
            'invoice-%s/unit-%d-year-%d.%s',
            $type === self::TYPE_LOGO ? 'logos' : 'stamps',
            $setting->getUnit(),
            $setting->getYear(),
            $extension,
        );
    }

    private function getImagePath(InvoiceUnitSetting $setting, string $type): ?string
    {
        return $type === self::TYPE_LOGO ? $setting->getLogoImagePath() : $setting->getStampImagePath();
    }

    private function toRelativePath(string $path): string
    {
        if (str_starts_with($path, $this->uploadDirectory.'/')) {
            return ltrim((string) substr($path, strlen($this->uploadDirectory)), '/');
        }

        return $path;
    }
}
