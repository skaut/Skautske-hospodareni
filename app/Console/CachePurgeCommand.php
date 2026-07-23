<?php

declare(strict_types=1);

namespace App\Console;

use FilesystemIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CachePurgeCommand extends Command
{
    protected static $defaultName = 'app:cache:purge';

    /**
     * @param string[] $dirs
     */
    public function __construct(private array $dirs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Clear temp folders and others');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Cache Purge');

        foreach ($this->dirs as $directory) {
            $style->text(sprintf('Purging: %s', $directory));
            $this->clearDirectory($directory);
        }

        $style->success(sprintf('Purging done. Total %d folders purged.', count($this->dirs)));

        return Command::SUCCESS;
    }

    private function clearDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if (! $item instanceof SplFileInfo) {
                continue;
            }

            $path = $item->getPathname();

            if ($item->isDir() && ! $item->isLink()) {
                $this->clearDirectory($path);

                if ($item->getBasename() !== 'nette.robotLoader') {
                    rmdir($path);
                }

                continue;
            }

            unlink($path);
        }
    }
}
