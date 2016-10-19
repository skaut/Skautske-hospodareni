<?php

namespace Model;

use \Nette\Caching\Cache;

/**
 * @author Hána František <sinacek@gmail.com>
 * Třída pro odvozování tříd, které jsou přispůsobiltené parametry v konstruktoru
 * $longName a $name chci sjednotit - prověřit, jestli to ma stale skautis ruzně
 */
abstract class MutableBaseService extends BaseService {

    protected $typeName;
    public $type;

    /** @var \Nette\Caching\Cache */
    protected $cache;

    public function __construct($name, \Skautis\Skautis $skautIS, \Nette\Caching\IStorage $cacheStorage, \Dibi\Connection $connection) {
        parent::__construct($skautIS, $connection);
        $this->typeName = $name;
        $this->type = strtolower($name);
        $cache = new Cache($cacheStorage, __CLASS__);
        $this->cache = $cache;
//        $this->cache->clean();
    }

}
