<?php
declare(strict_types=1);

namespace App\Console;

use Contributte\Psr6\ICachePoolFactory;
use Nette\Caching\Cache as NetteCache;
use Nette\Caching\Storage as NetteStorage;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class DoctrineCacheClearCommand extends Command
{
    protected static $defaultName = 'doctrine:cache:clear';

    /** @var string[] */
    private array $poolNames = ['annotations', 'metadata', 'query', 'result', 'secondLevel', 'enums'];

    public function __construct(
        private ICachePoolFactory $psr6Factory,
        private NetteStorage $netteStorage,
        private string $tempDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clear Doctrine/Nette caches used by EntityManagerFactory.')
            ->addOption('pool', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Limit to selected pool(s) (annotations|metadata|query|result|secondLevel|enums)')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List configured pools and exit')
            ->addOption('purge-dirs', null, InputOption::VALUE_NONE, 'Also remove cache directories from %tempDir%');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('list')) {
            foreach ($this->poolNames as $name) {
                $output->writeln('pool: doctrine.' . $name);
            }
            $output->writeln('ns: doctrine.annotations   (Nette FileStorage)');
            $output->writeln('dir: ' . $this->tempDir . '/_doctrine.annotations');
            $output->writeln('dir: ' . $this->tempDir . '/doctrine/proxies');
            return Command::SUCCESS;
        }

        $limit = $input->getOption('pool');
        $targetPools = $limit ? array_values(array_intersect($this->poolNames, $limit)) : $this->poolNames;

        // PSR-6 pooly (přes stejný factory jako v EntityManagerFactory)
        foreach ($targetPools as $name) {
            $pool = $this->psr6Factory->create('doctrine.' . $name);
            $ok = $pool->clear();
            $output->writeln(sprintf('pool: doctrine.%s: %s', $name, $ok ? '<info>cleared</info>' : '<error>failed</error>'));
        }

        // Nette namespace pro anotace (adresář _doctrine.annotations)
        $nette = new NetteCache($this->netteStorage, 'doctrine.annotations');
        $nette->clean([NetteCache::All => true]);
        $output->writeln('ns: doctrine.annotations: <info>cleared</info>');

        // Volitelně fyzicky smazat adresáře v %tempDir%
        if ($input->getOption('purge-dirs')) {
            foreach ([$this->tempDir . '/_doctrine.annotations', $this->tempDir . '/doctrine/proxies'] as $dir) {
                try {
                    if (is_dir($dir)) {
                        FileSystem::delete($dir);
                        $output->writeln('dir: ' . $dir . ': <info>deleted</info>');
                    }
                } catch (\Throwable $e) {
                    $output->writeln('dir: ' . $dir . ': <error>' . $e->getMessage() . '</error>');
                }
            }
        }

        return Command::SUCCESS;
    }
}
