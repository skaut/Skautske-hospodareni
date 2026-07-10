<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\Common\FileNotFound;
use App\Model\Common\Storage\AbstractStorage;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function is_file;
use function sprintf;
use function str_starts_with;
use function trim;

final class BugReportScreenshotStorage extends AbstractStorage
{
    public const DIRECTORY = 'bug-report-screenshots';
    public const DEFAULT_CONTENT_TYPE = 'application/octet-stream';
    public const MAX_FILE_SIZE = 5_242_880;

    public function __construct(FilesystemOperator $filesystem, string $uploadDirectory)
    {
        parent::__construct($filesystem, $uploadDirectory);
    }

    public function save(FileUpload $upload): ?BugReportScreenshot
    {
        if (! $upload->hasFile()) {
            return null;
        }

        if (! $upload->isOk()) {
            throw new InvalidArgumentException('Screenshot se nepodařilo nahrát.');
        }

        if ($upload->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Screenshot může mít nejvýše 5 MB.');
        }

        $contentType = $upload->getContentType() ?? self::DEFAULT_CONTENT_TYPE;
        if ($upload->getImageSize() === null || ! str_starts_with($contentType, 'image/')) {
            throw new InvalidArgumentException('Screenshot musí být platný obrázek.');
        }

        $extension = $upload->getSuggestedExtension() ?? 'bin';
        $relativePath = $this->generateUniquePath(self::DIRECTORY, $extension);
        $this->writeUploadPath($relativePath, $upload);

        return new BugReportScreenshot(
            $relativePath,
            $this->normalizeOriginalName($upload->getSanitizedName(), $extension),
            $contentType,
            $upload->getSize(),
        );
    }

    public function getAbsolutePath(TechnicalErrorReport $report): ?string
    {
        $relativePath = $report->getScreenshotPath();
        if ($relativePath === null) {
            return null;
        }

        $absolutePath = $this->getLocalPath($relativePath);

        return $absolutePath !== null && is_file($absolutePath) ? $absolutePath : null;
    }

    public function requireAbsolutePath(TechnicalErrorReport $report): string
    {
        $absolutePath = $this->getAbsolutePath($report);
        if ($absolutePath === null) {
            throw new RuntimeException(sprintf('Screenshot for technical error report #%d was not found.', $report->getId()));
        }

        return $absolutePath;
    }

    public function getStream(TechnicalErrorReport $report): ?StreamInterface
    {
        $relativePath = $report->getScreenshotPath();
        if ($relativePath === null) {
            return null;
        }

        try {
            return $this->readStreamPath($relativePath);
        } catch (FileNotFound) {
            return null;
        }
    }

    public function getContents(TechnicalErrorReport $report): ?string
    {
        $relativePath = $report->getScreenshotPath();
        if ($relativePath === null) {
            return null;
        }

        try {
            return $this->readPath($relativePath);
        } catch (FileNotFound) {
            return null;
        }
    }

    private function normalizeOriginalName(string $name, string $fallbackExtension): string
    {
        $name = trim(Strings::webalize($name, '.', false), '.-');

        return $name !== '' ? $name : 'screenshot.'.$fallbackExtension;
    }
}
