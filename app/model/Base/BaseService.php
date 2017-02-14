<?php

namespace Model;

use Dibi\Connection;
use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
abstract class BaseService extends \Nette\Object {

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
     * @var \Skautis\Skautis
     */
    protected $skautis;

    /**
     * připojení k databázi
     * @var type 
     */
    protected $connection;

    /**
     * používat lokální úložiště?
     * @var bool
     */
    private $useCache = TRUE;

    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU
     * @var type 
     */
    private static $storage = array();

    public function __construct(Skautis $skautis = NULL, Connection $connection = NULL) {
        $this->skautis = $skautis;
        $this->connection = $connection;
    }

    /**
     * ukládá $val do lokálního úložiště
     * @param mixed $id
     * @param mixed $val
     * @return mixed 
     */
    protected function saveSes($id, $val) {
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
    protected function loadSes($id) {
        if ($this->useCache && array_key_exists($id, self::$storage)) {
            return self::$storage[$id];
        }
        return FALSE;
    }

    /**
     * vrátí pdf do prohlizece
     * @param type $template
     * @param string $filename
     * @return pdf 
     */
    function makePdf($template = NULL, $filename = NULL, $landscape = FALSE) {
        $format = $landscape ? "A4-L" : "A4";
        if ($template === NULL) {
            return FALSE;
        }
//        define('_MPDF_PATH', LIBS_DIR . '/mpdf/');
//        require_once(_MPDF_PATH . 'mpdf.php');
        $mpdf = new \mPDF(
                'utf-8', $format, $default_font_size = 0, $default_font = '', $mgl = 10, $mgr = 10, $mgt = 10, $mgb = 10, $mgh = 9, $mgf = 9, $orientation = 'P'
        );

        $mpdf->WriteHTML((string) $template, NULL);
        $mpdf->Output($filename, 'I');
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na čtení
     * @param \Nette\Security\User $user
     * @return type
     */
    public function getReadUnits(\Nette\Security\User $user) {
        $res = array();
        foreach ($user->getIdentity()->access[self::ACCESS_READ] as $uId => $u) {
            $res[$uId] = $u->DisplayName;
        }
        return $res;
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na zápis a editaci
     * @param \Nette\Security\User $user
     * @return type
     */
    public function getEditUnits(\Nette\Security\User $user) {
        $res = array();
        foreach ($user->getIdentity()->access[self::ACCESS_EDIT] as $uId => $u) {
            $res[$uId] = $u->DisplayName;
        }
        return $res;
    }

}
