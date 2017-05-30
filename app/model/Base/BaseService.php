<?php

namespace Model;

use Nette;
use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 * @deprecated Don't inherit from this service
 */
abstract class BaseService extends Nette\Object
{

    //konstanty pro Event a Camp
    const TYPE_CAMP = "camp";
    const TYPE_GENERAL = "general";
    const TYPE_UNIT = "unit";
    //konstanty pro uživatelskou identitu
    const ACCESS_READ = 'read';
    const ACCESS_EDIT = 'edit';


    /**
     * slouží pro komunikaci se skautISem
     * @var Skautis|NULL
     */
    protected $skautis;

    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU
     * @var array
     */
    private static $storage = [];

    public function __construct(Skautis $skautis = NULL)
    {
        $this->skautis = $skautis;
    }

    /**
     * ukládá $val do lokálního úložiště
     * @param mixed $id
     * @param mixed $val
     * @return mixed
     */
    protected function saveSes($id, $val)
    {
        return self::$storage[$id] = $val;
    }

    /**
     * vrací objekt z lokálního úložiště
     * @param string|int $id
     * @return mixed | FALSE
     */
    protected function loadSes($id)
    {
        if (array_key_exists($id, self::$storage)) {
            return self::$storage[$id];
        }
        return FALSE;
    }

}
