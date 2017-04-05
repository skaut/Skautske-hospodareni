<?php

namespace Model\Infrastructure;

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Kdyby\DoctrineCache\DI\Helpers as KdybyHelpers;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use Nette\DI\Statement;
use Fmasa\DoctrineYamlAnnotations\YamlReader;

class YamlAnnotationsExtension extends CompilerExtension
{

    private const DEFAULTS = [
        'debug' => FALSE,
        'aliases' => [],
    ];


    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        /* @var $builder \Nette\DI\ContainerBuilder */

        $config = Helpers::merge($this->getConfig(), self::DEFAULTS);

        $builder->addDefinition($this->prefix('reader'))
            ->setClass(CachedReader::class)
            ->setArguments([
                new Statement(YamlReader::class, [
                    new Statement(self::class.'::getOrmConfiguration'),
                    $config['aliases'],
                ]),
                KdybyHelpers::processCache($this, 'default', 'yamlAnnotations', $config['debug'])
            ])->setAutowired(FALSE);
    }

    public static function getOrmConfiguration(EntityManager $em): Configuration
    {
        return $em->getConfiguration();
    }


}
