<?php

declare(strict_types=1);

namespace App\Model\Common\Storage;

use App\Model\Common\FileNotFound;
use Assert\Assertion;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function chmod;
use function clearstatcache;
use function dirname;
use function fclose;
use function fopen;
use function is_dir;
use function is_writable;
use function ltrim;
use function random_bytes;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function trim;

abstract class AbstractStorage
{
    public function __construct(
        private FilesystemOperator $filesystem,
        private ?string $localRootDirectory = null,
    ) {
        $this->localRootDirectory = $localRootDirectory !== null ? rtrim($localRootDirectory, '/') : null;
    }

    protected function readStreamPath(string $path): StreamInterface
    {
        try {
            $contents = $this->filesystem->readStream($this->normalizePath($path));
            Assertion::isResource($contents);

            return Utils::streamFor($contents);
        } catch (UnableToReadFile $e) {
            throw new FileNotFound($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function readPath(string $path): string
    {
        try {
            return $this->filesystem->read($this->normalizePath($path));
        } catch (UnableToReadFile $e) {
            throw new FileNotFound($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function writePath(string $path, string $contents): void
    {
        $path = $this->normalizePath($path);
        $this->ensureWritableLocalDirectory($path);
        $this->filesystem->write($path, $contents);
    }

    protected function writeUploadPath(string $path, FileUpload $upload): void
    {
        $path = $this->normalizePath($path);
        $this->ensureWritableLocalDirectory($path);

        $stream = fopen($upload->getTemporaryFile(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open uploaded file stream.');
        }

        try {
            $this->filesystem->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }
    }

    protected function deletePath(string $path): void
    {
        try {
            $this->filesystem->delete($this->normalizePath($path));
        } catch (FilesystemException) {
            // The application treats repeated deletes as idempotent cleanup.
        }
    }

    protected function existsPath(string $path): bool
    {
        try {
            return $this->filesystem->fileExists($this->normalizePath($path));
        } catch (FilesystemException) {
            return false;
        }
    }

    protected function generateUniquePath(string $directory, string $extension): string
    {
        $directory = trim($this->normalizePath($directory), '/');
        $extension = ltrim($extension, '.');

        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $path = $directory.'/'.bin2hex(random_bytes(16)).'.'.$extension;
            if (! $this->existsPath($path)) {
                return $path;
            }
        }

        throw new RuntimeException('Unable to generate unique file path.');
    }

    protected function getLocalPath(string $path): ?string
    {
        if ($this->localRootDirectory === null) {
            return null;
        }

        return $this->localRootDirectory.'/'.$this->normalizePath($path);
    }

    protected function normalizePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, '..')) {
            throw new RuntimeException(sprintf('Invalid storage path "%s".', $path));
        }

        return $path;
    }

    private function ensureWritableLocalDirectory(string $path): void
    {
        $localPath = $this->getLocalPath($path);
        if ($localPath === null) {
            return;
        }

        $directory = dirname($localPath);
        FileSystem::createDir($directory, 0775);
        clearstatcache(true, $directory);

        if (! is_dir($directory)) {
            throw new RuntimeException(sprintf('Storage directory "%s" could not be created.', $directory));
        }

        if (is_writable($directory)) {
            return;
        }

        @chmod($directory, 0775);
        clearstatcache(true, $directory);

        if (! is_writable($directory)) {
            throw new RuntimeException(sprintf('Storage directory "%s" is not writable.', $directory));
        }
    }
}
