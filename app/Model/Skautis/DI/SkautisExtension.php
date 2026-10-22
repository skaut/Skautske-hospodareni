<?php

declare(strict_types=1);

namespace App\Model\Skautis\DI;

use App\Model\Skautis\DI\Tracy\Panel;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Skautis;
use stdClass;
use Tracy\Debugger;

use function class_exists;

/**
 * Registrace Skautis knihovny do Nette DI kontejneru.
 *
 * Inlinováno z opuštěného balíčku skautis/nette (poslední verze 2.2.0 podporovala jen nette/utils ^3).
 * Registruje stejné služby pod stejnými názvy jako původní rozšíření, takže konfigurace i wiring v
 * app/config/skautis.neon zůstávají beze změny.
 */
final class SkautisExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'applicationId' => Expect::string()->nullable(),
            'testMode' => Expect::bool(false),
            'profiler' => Expect::bool()->nullable(),
            'cache' => Expect::bool(true),
            'compression' => Expect::bool(true),
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        /** @var stdClass $config */
        $config = $this->getConfig();
        $profiler = $config->profiler ?? ! empty($builder->parameters['debugMode']);

        $builder->addDefinition($this->prefix('config'))
            ->setFactory(Skautis\Config::class, [$config->applicationId, $config->testMode, $config->cache, $config->compression]);

        $builder->addDefinition($this->prefix('webServiceFactory'))
            ->setFactory(Skautis\Wsdl\WebServiceFactory::class);

        $manager = $builder->addDefinition($this->prefix('wsdlManager'))
            ->setFactory(Skautis\Wsdl\WsdlManager::class);

        $builder->addDefinition($this->prefix('session'))
            ->setFactory(SessionAdapter::class);

        $builder->addDefinition($this->prefix('user'))
            ->setFactory(Skautis\User::class);

        $builder->addDefinition($this->prefix('skautis'))
            ->setFactory(Skautis\Skautis::class);

        if ($profiler && class_exists(Debugger::class)) {
            $builder->addDefinition($this->prefix('panel'))
                ->setFactory(Panel::class);
            $manager->addSetup(['@'.$this->prefix('panel'), 'register'], ['@'.$this->prefix('wsdlManager')]);
        }
    }
}
