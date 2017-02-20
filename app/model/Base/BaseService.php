<?php

namespace Model;

use Nette;
use Nette\Security\User;
use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
abstract class BaseService extends Nette\Object
{

    //konstanty pro Event a Camp
    const LEADER = 0; //ID v poli funkcí
    const ASSISTANT = 1; //ID v poli funkcí
    const ECONOMIST = 2; //ID v poli funkcí
    const TYPE_CAMP = "camp";
    const TYPE_GENERAL = "general";
    const TYPE_UNIT = "unit";
    //konstanty pro uživatelskou identitu
    const ACCESS_READ = 'read';
    const ACCESS_EDIT = 'edit';

    /**
     * věková hranice pro dítě
     */
    const ADULT_AGE = 18;

    /**
     * slouží pro komunikaci se skautISem
     * @var Skautis|NULL
     */
    protected $skautis;

    /**
     * používat lokální úložiště?
     * @var bool
     */
    private $useCache = TRUE;

    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU
     * @var type
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
        if ($this->useCache) {
            self::$storage[$id] = $val;
        }
        return $val;
    }

    /**
     * vrací objekt z lokálního úložiště
     * @param string|int $id
     * @return mixed | FALSE
     */
    protected function loadSes($id)
    {
        if ($this->useCache && array_key_exists($id, self::$storage)) {
            return self::$storage[$id];
        }
        return FALSE;
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na čtení
     * @param User $user
     * @return array
     */
    public function getReadUnits(User $user): array
    {
        $res = [];
        foreach ($user->getIdentity()->access[self::ACCESS_READ] as $uId => $u) {
            $res[$uId] = $u->DisplayName;
        }
        return $res;
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na zápis a editaci
     * @param User $user
     * @return array
     */
    public function getEditUnits(User $user): array
    {
        $res = [];
        foreach ($user->getIdentity()->access[self::ACCESS_EDIT] as $uId => $u) {
            $res[$uId] = $u->DisplayName;
        }
        return $res;
    }

}
