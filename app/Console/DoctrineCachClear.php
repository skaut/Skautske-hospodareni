<?php

declare(strict_types=1);

namespace App\Console;

use App\Model\Infrastructure\Cache\DoctrineCachePool;
use App\Model\Infrastructure\Cache\DoctrineCachePoolFactory;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function in_array;
use function is_dir;
use function sprintf;

#[AsCommand(name: 'doctrine:cache:clear', description: 'Clear Doctrine caches used by EntityManagerFactory.')]
final class DoctrineCacheClearCommand extends Command
{
    public function __construct(
        private DoctrineCachePoolFactory $cachePoolFactory,
        private string $cacheDir,
        private string $proxyDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'pool',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Limit to selected pool(s) (%s)', implode('|', $this->poolValues())),
            )
            ->addOption('list', null, InputOption::VALUE_NONE, 'List configured pools and exit')
            ->addOption('purge-dirs', null, InputOption::VALUE_NONE, 'Also remove generated proxy classes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('list')) {
            foreach (DoctrineCachePool::cases() as $pool) {
                $output->writeln('pool: '.$pool->namespace());
            }

            $output->writeln('dir: '.$this->cacheDir);
            $output->writeln('dir: '.$this->proxyDir);

            return Command::SUCCESS;
        }

        foreach ($this->selectedPools($input) as $pool) {
            $cleared = $this->cachePoolFactory->create($pool)->clear();
            $output->writeln(sprintf(
                'pool: %s: %s',
                $pool->namespace(),
                $cleared ? '<info>cleared</info>' : '<error>failed</error>',
            ));
        }

        if ($input->getOption('purge-dirs')) {
            $this->purgeProxies($output);
        }

        return Command::SUCCESS;
    }

    /** @return list<DoctrineCachePool> */
    private function selectedPools(InputInterface $input): array
    {
        $limit = $input->getOption('pool');

        if ($limit === []) {
            return DoctrineCachePool::cases();
        }

        return array_values(array_filter(
            DoctrineCachePool::cases(),
            static fn (DoctrineCachePool $pool): bool => in_array($pool->value, $limit, true),
        ));
    }

    /** @return list<string> */
    private function poolValues(): array
    {
        return array_map(static fn (DoctrineCachePool $pool): string => $pool->value, DoctrineCachePool::cases());
    }

    private function purgeProxies(OutputInterface $output): void
    {
        try {
            if (! is_dir($this->proxyDir)) {
                $output->writeln('dir: '.$this->proxyDir.': <comment>nothing to delete</comment>');

                return;
            }

            FileSystem::delete($this->proxyDir);
            $output->writeln('dir: '.$this->proxyDir.': <info>deleted</info>');
        } catch (Throwable $e) {
            $output->writeln('dir: '.$this->proxyDir.': <error>'.$e->getMessage().'</error>');
        }
    }
}
