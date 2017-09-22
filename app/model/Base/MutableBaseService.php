<?php

namespace Model;

use \Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Skautis\Skautis;

/**
 * Třída pro odvozování tříd, které jsou přispůsobiltené parametry v konstruktoru
 * $longName a $name chci sjednotit - prověřit, jestli to ma stale skautis ruzně
 */
abstract class MutableBaseService extends BaseService
{

    protected $typeName;
    public $type;

    /** @var Cache */
    protected $cache;

    public function __construct(string $name, Skautis $skautIS, IStorage $cacheStorage)
    {
        parent::__construct($skautIS);
        $this->typeName = $name;
        $this->type = strtolower($name);
        $cache = new Cache($cacheStorage, __CLASS__);
        $this->cache = $cache;
    }

}
