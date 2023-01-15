<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Unit;

use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Nette\Caching\Cache;
use Nette\Caching\Storage;

final class CachedUnitRepository implements IUnitRepository
{
    private const EXPIRATION = '1 day';

    private Cache $cache;

    public function __construct(private IUnitRepository $inner, Storage $storage)
    {
        $this->cache = new Cache($storage, 'units');
    }

    /** @return Unit[] */
    public function findByParent(int $parentId): array
    {
        return $this->cache->load(
            'byParent-' . $parentId,
            function (array|null &$options) use ($parentId): array {
                $options[Cache::EXPIRE] = self::EXPIRATION;

                return $this->inner->findByParent($parentId);
            },
        );
    }

    public function find(int $id): Unit
    {
        return $this->cache->load(
            'byId-' . $id,
            function (array|null &$options) use ($id): Unit {
                $options[Cache::EXPIRE] = self::EXPIRATION;

                return $this->inner->find($id);
            },
        );
    }
}
