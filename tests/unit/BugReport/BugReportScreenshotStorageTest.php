<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use Codeception\Test\Unit;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem as FileSystemUtil;

use function bin2hex;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagefill;
use function imagejpeg;
use function imagepng;
use function is_dir;
use function random_bytes;
use function str_starts_with;
use function tempnam;

final class BugReportScreenshotStorageTest extends Unit
{
    public function testJpegScreenshotIsStoredUnderGeneratedName(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension is required to create a JPEG fixture.');
        }

        $uploadDirectory = codecept_output_dir('bug-report-storage');
        FileSystemUtil::createDir($uploadDirectory);
        $upload = $this->createJpegUpload('problem screenshot.jpg');

        $screenshot = $this->createStorage($uploadDirectory)->save($upload);

        self::assertInstanceOf(BugReportScreenshot::class, $screenshot);
        self::assertTrue(str_starts_with($screenshot->getPath(), BugReportScreenshotStorage::DIRECTORY.'/'));
        self::assertSame('problem-screenshot.jpeg', $screenshot->getOriginalName());
        self::assertSame('image/jpeg', $screenshot->getContentType());
        self::assertStringEndsWith('.jpeg', $screenshot->getPath());
        self::assertTrue(file_exists($uploadDirectory.'/'.$screenshot->getPath()));
    }

    public function testPngScreenshotIsStoredUnderGeneratedName(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension is required to create a PNG fixture.');
        }

        $uploadDirectory = codecept_output_dir('bug-report-storage');
        FileSystemUtil::createDir($uploadDirectory);
        $upload = $this->createPngUpload('problem screenshot.png');

        $screenshot = $this->createStorage($uploadDirectory)->save($upload);

        self::assertInstanceOf(BugReportScreenshot::class, $screenshot);
        self::assertTrue(str_starts_with($screenshot->getPath(), BugReportScreenshotStorage::DIRECTORY.'/'));
        self::assertSame('problem-screenshot.png', $screenshot->getOriginalName());
        self::assertSame('image/png', $screenshot->getContentType());
        self::assertStringEndsWith('.png', $screenshot->getPath());
        self::assertTrue(file_exists($uploadDirectory.'/'.$screenshot->getPath()));
    }

    public function testMissingScreenshotDirectoryIsCreatedWithoutRemovingExistingFiles(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension is required to create a PNG fixture.');
        }

        $uploadDirectory = codecept_output_dir('bug-report-storage-'.bin2hex(random_bytes(4)));
        FileSystemUtil::createDir($uploadDirectory);
        file_put_contents($uploadDirectory.'/existing-file.txt', 'keep');

        $screenshotDirectory = $uploadDirectory.'/'.BugReportScreenshotStorage::DIRECTORY;
        self::assertFalse(is_dir($screenshotDirectory));

        $upload = $this->createPngUpload('problem screenshot.png');
        $screenshot = $this->createStorage($uploadDirectory)->save($upload);

        self::assertInstanceOf(BugReportScreenshot::class, $screenshot);
        self::assertTrue(is_dir($screenshotDirectory));
        self::assertTrue(file_exists($uploadDirectory.'/existing-file.txt'));
        self::assertSame('keep', file_get_contents($uploadDirectory.'/existing-file.txt'));
        self::assertTrue(file_exists($uploadDirectory.'/'.$screenshot->getPath()));
    }

    public function testNonImageScreenshotIsRejected(): void
    {
        $path = tempnam(codecept_output_dir(), 'bug-report-upload');
        self::assertIsString($path);
        file_put_contents($path, 'not an image');

        $upload = new FileUpload([
            'name' => 'screenshot.txt',
            'type' => 'text/plain',
            'size' => 12,
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Screenshot musí být platný obrázek.');

        $uploadDirectory = codecept_output_dir('bug-report-storage');

        $this->createStorage($uploadDirectory)->save($upload);
    }

    private function createStorage(string $uploadDirectory): BugReportScreenshotStorage
    {
        return new BugReportScreenshotStorage(
            new Filesystem(new LocalFilesystemAdapter($uploadDirectory)),
            $uploadDirectory,
        );
    }

    private function createJpegUpload(string $name): FileUpload
    {
        $path = tempnam(codecept_output_dir(), 'bug-report-upload');
        self::assertIsString($path);

        $image = imagecreatetruecolor(1, 1);
        self::assertNotFalse($image);
        $color = imagecolorallocate($image, 255, 255, 255);
        self::assertNotFalse($color);
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $path);
        imagedestroy($image);

        return new FileUpload([
            'name' => $name,
            'type' => 'image/jpeg',
            'size' => 100,
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    private function createPngUpload(string $name): FileUpload
    {
        $path = tempnam(codecept_output_dir(), 'bug-report-upload');
        self::assertIsString($path);

        $image = imagecreatetruecolor(1, 1);
        self::assertNotFalse($image);
        $color = imagecolorallocate($image, 255, 255, 255);
        self::assertNotFalse($color);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);
        imagedestroy($image);

        return new FileUpload([
            'name' => $name,
            'type' => 'image/png',
            'size' => 100,
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
        ]);
    }
}
