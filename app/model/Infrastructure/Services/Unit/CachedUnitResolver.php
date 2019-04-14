<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Unit;

use Model\Payment\IUnitResolver;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

final class CachedUnitResolver implements IUnitResolver
{
    /** @var IUnitResolver */
    private $inner;

    /** @var Cache */
    private $cache;

    public function __construct(IUnitResolver $inner, IStorage $storage)
    {
        $this->inner = $inner;
        $this->cache = new Cache($storage, 'cached-unit-resolver');
    }

    public function getOfficialUnitId(int $unitId) : int
    {
        return $this->cache->load(
            $unitId,
            function () use ($unitId) : int {
                return $this->inner->getOfficialUnitId($unitId);
            }
        );
    }
}
