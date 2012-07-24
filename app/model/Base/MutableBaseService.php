<?php

/**
 * @author Hána František
 * Třída pro odvozování tříd, které jsou přispůsobiltené parametry v konstruktoru
 */
abstract class MutableBaseService extends BaseService {

    static protected $typeName;
    static protected $typeLongName;
    static protected $expire;

    /** @var FileStorage */
    protected $cache;

    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage) {
        self::$typeName = $name;
        self::$typeLongName = $longName;
        self::$expire = $expire;

        parent::__construct($skautIS);
        $cache = new Cache($cacheStorage, __CLASS__);
        $this->cache = $cache;
        $this->cache->clean();
    }

}

