<?php

declare(strict_types=1);

namespace Model;

use Skautis\Skautis;
use function array_key_exists;

/**
 * @deprecated Don't inherit from this service
 */
abstract class BaseService
{
    public const ACCESS_READ = 'read';
    public const ACCESS_EDIT = 'edit';

    /**
     * slouží pro komunikaci se skautISem
     *
     * @var Skautis
     */
    protected $skautis;

    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU
     *
     * @var mixed[]
     */
    private static array $storage = [];

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * ukládá $val do lokálního úložiště
     *
     * @param mixed $id
     * @param mixed $val
     *
     * @return mixed
     */
    protected function saveSes($id, $val)
    {
        return self::$storage[$id] = $val;
    }

    /**
     * vrací objekt z lokálního úložiště
     *
     * @param string|int $id
     *
     * @return mixed | FALSE
     */
    protected function loadSes($id)
    {
        if (array_key_exists($id, self::$storage)) {
            return self::$storage[$id];
        }

        return false;
    }
}
