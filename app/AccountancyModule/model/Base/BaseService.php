<?php

/**
 * @author sinacek
 */
class BaseService extends Object {

    /**
     * reference na třídu typu Table
     * @var instance of BaseTable
     */
    protected $table;
    
    /**
     * slouží pro komunikaci se skautISem
     * @var SkautisService
     */
    protected $skautIS;
    /**
     * používat lokální úložiště?
     * @var bool
     */
    private $useCache = TRUE;
    /**
     * lokální úložiště pro daný požadavek
     * @var type 
     */
    private static $storage;

    public function __construct() {
        $this->skautIS = SkautisService::getInstance();
        self::$storage = array();
    }
    
    /**
     * ukládá $val do lokálního úložiště
     * @param mixed $id
     * @param mixed $val
     * @return mixed 
     */
    protected function save($id, $val){
        if($this->useCache)
            self::$storage[$id] = $val;
        return $val;
    }
    
    /**
     * vrací objekt z lokálního pole
     * @param string|int $id
     * @return mixed 
     */
    protected function load($id){
        if( $this->useCache && array_key_exists($id, self::$storage) )
            return self::$storage[$id];
        return FALSE;
    }
    
    /**
     * vrátí pdf do prohlizece
     * @param type $template
     * @param string $filename
     * @return pdf 
     */
    function makePdf($template = NULL, $filename = NULL) {
        if ($template === NULL)
            return FALSE;
        define('_MPDF_PATH', LIBS_DIR . '/mpdf/');
        require_once(_MPDF_PATH . 'mpdf.php');
        $mpdf = new mPDF('utf-8');
        $mpdf->WriteHTML((string) $template, NULL);
        $mpdf->Output($filename, 'I');
    }


}

