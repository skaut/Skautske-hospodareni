<?php

declare(strict_types=1);

namespace App\Console;

use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Bridges\FormsLatte\FormMacros;
use Nette\Utils\Strings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Safe\realpath;
use function sprintf;

final class LintLatte extends Command
{
    private string $appDir;

    private ILatteFactory $latteFactory;

    public function __construct(string $appDir, ILatteFactory $latteFactory)
    {
        parent::__construct();
        $this->appDir       = $appDir;
        $this->latteFactory = $latteFactory;
    }

    protected function configure() : void
    {
        $this->setName('app:lint-latte');
        $this->setDescription('Lints all Latte templates in application');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $latte = $this->latteFactory->create();

        UIMacros::install($latte->getCompiler());
        FormMacros::install($latte->getCompiler());

        $appDir = realpath($this->appDir);

        $latteFiles = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($appDir))),
            '~.latte$~'
        );

        foreach ($latteFiles as $filePath => $_) {
            $shortPath = Strings::substring($filePath, Strings::length($appDir));

            $output->writeln(sprintf('<fg=yellow>Compiling %s...</>', $shortPath));
            $latte->compile($filePath);

            $output->writeln(sprintf('<fg=green>Template %s was compiled successfully</>', $shortPath));
        }

        return 0;
    }
}
