<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Cache;

/**
 * Cache pooly, které Doctrine v aplikaci používá.
 *
 * Enum je jediný zdroj pravdy o jejich jménech — dřív byl seznam duplikovaný v
 * {@see \App\Console\DoctrineCacheClearCommand} a rozešel se s realitou: mazaly se pooly
 * `doctrine.annotations` a `doctrine.enums`, které po odstranění doctrine/annotations
 * a enum postLoad listeneru už nikdo nevytvářel.
 *
 * Pool pro second-level cache tu záměrně není — ta je vypnutá, viz
 * {@see \App\Model\Infrastructure\EntityManagerFactory::create()}.
 */
enum DoctrineCachePool: string
{
    case Metadata = 'metadata';
    case Query = 'query';
    case Result = 'result';

    /**
     * Jmenný prostor poolu, tedy i název podadresáře v cache.
     */
    public function namespace(): string
    {
        return 'doctrine.'.$this->value;
    }
}
