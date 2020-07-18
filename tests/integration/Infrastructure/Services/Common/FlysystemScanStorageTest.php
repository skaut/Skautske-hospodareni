<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Common;

use Codeception\Test\Unit;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Model\Common\Exception\InvalidScanFile;
use Model\Common\FileNotFound;
use Model\Common\FilePath;
use Nette\Utils\FileSystem as FileSystemUtil;
use Nette\Utils\Image;
use function uniqid;

final class FlysystemScanStorageTest extends Unit
{
    private const FILE_PATH_PREFIX = 'test';

    private string $directory;

    private FlysystemScanStorage $storage;

    protected function _before() : void
    {
        $this->directory = __DIR__ . '/../../../../_temp/' . uniqid(self::class, true);
        $this->storage   = new FlysystemScanStorage(new Filesystem(new Local($this->directory)));
    }

    protected function _after() : void
    {
        FileSystemUtil::delete($this->directory);
    }

    public function testSaveFile() : void
    {
        $contents = Image::fromBlank(1, 1)->toString();

        $filepath = $this->getFilePath('foo');
        $this->storage->save($filepath, $contents);

        $this->assertSame(
            $contents,
            FileSystemUtil::read($this->directory . '/' . $filepath->getPath()),
        );
    }

    public function testGetThrowsExceptionIfFileDoesNotExist() : void
    {
        $this->expectException(FileNotFound::class);

        $this->storage->get($this->getFilePath('unknown-file.jpg'));
    }

    public function testDeleteRemovesFile() : void
    {
        $this->storage->save($this->getFilePath('foo'), Image::fromBlank(1, 1)->toString());
        $this->storage->delete($this->getFilePath('foo'));

        $this->assertFileNotExists($this->directory . '/' . FilePath::generatePath(self::FILE_PATH_PREFIX, 'foo'));
    }

    public function testDeleteWithNonexistentFileDoesNothing() : void
    {
        $this->storage->delete($this->getFilePath('unknown-file.jpg'));
    }

    public function testGetReturnsCorrectFile() : void
    {
        $contents = Image::fromBlank(1, 1)->toString();
        $filename = 'foo.jpg';
        $filepath = $this->getFilePath($filename);
        $this->storage->save($filepath, $contents);

        $file = $this->storage->get($filepath);

        $this->assertSame($filename, $file->getOriginalFileName());
        $this->assertSame($filepath->getPath(), $file->getPath());
        $this->assertStringEndsWith($filename, $file->getPath());
        $this->assertSame($contents, (string) $file->getContents());
    }

    /**
     * @dataProvider dataInvalidContents
     */
    public function testCannotSaveFileWithInvalidMimeType(string $contents) : void
    {
        $this->expectException(InvalidScanFile::class);

        $this->storage->save($this->getFilePath('foo'), $contents);
    }

    /**
     * @return string[][]
     */
    public function dataInvalidContents() : array
    {
        return [
            [Image::fromBlank(1, 1)->toString(Image::GIF)],
            [''],
        ];
    }

    private function getFilePath(string $fileName) : FilePath
    {
        return FilePath::generate(self::FILE_PATH_PREFIX, $fileName);
    }
}
