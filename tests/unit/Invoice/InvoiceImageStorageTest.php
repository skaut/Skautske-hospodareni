<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use App\Model\Invoice\Entity\InvoiceUnitSetting;
use Codeception\Test\Unit;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem as FileSystemUtil;

use function file_exists;
use function function_exists;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagefill;
use function imagepng;
use function tempnam;

final class InvoiceImageStorageTest extends Unit
{
    public function testImageIsStoredUnderUnitAndYearPath(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension is required to create a PNG fixture.');
        }

        $uploadDirectory = codecept_output_dir('invoice-images');
        FileSystemUtil::createDir($uploadDirectory);
        $storage = $this->createStorage($uploadDirectory);
        $setting = $this->createSetting();

        $path = $storage->replace($setting, $this->createPngUpload('logo.png'), InvoiceImageStorage::TYPE_LOGO);

        self::assertSame('invoice-logos/unit-123-year-2026.png', $path);
        self::assertTrue(file_exists($uploadDirectory.'/'.$path));
        self::assertTrue($storage->exists($path));
        self::assertSame($uploadDirectory.'/'.$path, $storage->getReadableLocalPath($path));
        self::assertSame('image/png', $storage->getContentType($path));
        self::assertSame('logo', $storage->getDownloadName(InvoiceImageStorage::TYPE_LOGO));
    }

    public function testDeleteRemovesStoredImage(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension is required to create a PNG fixture.');
        }

        $uploadDirectory = codecept_output_dir('invoice-images');
        FileSystemUtil::createDir($uploadDirectory);
        $storage = $this->createStorage($uploadDirectory);
        $path = $storage->replace($this->createSetting(), $this->createPngUpload('stamp.png'), InvoiceImageStorage::TYPE_STAMP);

        $storage->delete($path);

        self::assertFalse($storage->exists($path));
        self::assertFalse(file_exists($uploadDirectory.'/'.$path));
    }

    private function createStorage(string $uploadDirectory): InvoiceImageStorage
    {
        return new InvoiceImageStorage(
            new Filesystem(new LocalFilesystemAdapter($uploadDirectory)),
            $uploadDirectory,
        );
    }

    private function createSetting(): InvoiceUnitSetting
    {
        return new InvoiceUnitSetting(
            123,
            2026,
            'Středisko',
            'Ulice 1',
            'Praha',
            '10000',
            '12345678',
        );
    }

    private function createPngUpload(string $name): FileUpload
    {
        $path = tempnam(codecept_output_dir(), 'invoice-image-upload');
        self::assertIsString($path);

        $image = imagecreatetruecolor(1, 1);
        self::assertNotFalse($image);
        $color = imagecolorallocate($image, 255, 255, 255);
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
