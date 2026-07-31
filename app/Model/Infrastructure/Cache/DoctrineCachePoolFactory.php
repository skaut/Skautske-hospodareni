<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Vyrábí PSR-6 pooly pro Doctrine cache nad symfony/cache.
 *
 * Doctrine chce PSR-6, Nette má vlastní cache API. Dřív ten rozdíl přemostil adaptér
 * `skaut/psr6-caching` — fork opuštěného `contributte/psr6-caching` bez jediného stable releasu,
 * který navíc type-hintoval `Nette\Caching\IStorage`, jenž v nette/caching už neexistuje a funguje
 * jen přes deprecated `class_alias` shim. symfony/cache je v projektu už jako přímá závislost
 * a PSR-6 umí nativně, takže most není potřeba.
 *
 * Pooly zůstávají v `%tempDir%/cache`, aby je `app:cache:purge` (maže ten adresář) dál uklidil.
 */
final class DoctrineCachePoolFactory
{
    public function __construct(private string $cacheDir)
    {
    }

    public function create(DoctrineCachePool $pool): CacheItemPoolInterface
    {
        // Bez expirace — Doctrine si obsah invaliduje sama, pooly se mažou při deployi/purge.
        return new FilesystemAdapter($pool->namespace(), 0, $this->cacheDir);
    }
}
