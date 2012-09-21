<?php

/**
 * @author Hána František
 */
abstract class BaseService extends Object {

    //konstanty pro Event a Camp
    const LEADER = 0; //ID v poli funkcí
    const ASSISTANT = 1; //ID v poli funkcí
    const ECONOMIST = 2; //ID v poli funkcí
    
    /**
     * reference na třídu typu Table
     * @var instance of BaseTable
     */
    protected $table;
    
    /**
     * slouží pro komunikaci se skautISem
     * @var SkautIS
     */
    protected $skautIS;
    
    /**
     * používat lokální úložiště?
     * @var bool
     */
    private $useCache = TRUE;
    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU
     * @var type 
     */
    private static $storage;

    public function __construct($skautIS = NULL) {
        $this->skautIS = $skautIS;
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
     * vrací objekt z lokálního úložiště
     * @param string|int $id
     * @return mixed | FALSE
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
    function makePdf($template = NULL, $filename = NULL, $landscape = false) {
        $format = $landscape ? "A4-L" : "A4";
        if ($template === NULL)
            return FALSE;
        define('_MPDF_PATH', LIBS_DIR . '/mpdf/');
        require_once(_MPDF_PATH . 'mpdf.php');
        $mpdf = new mPDF(
                'utf-8',
                $format,
                $default_font_size=0,
                $default_font='',
                $mgl=10,
                $mgr=10,
                $mgt=10,
                $mgb=10,
                $mgh=9,
                $mgf=9,
                $orientation = 'P'
                );
        
        $mpdf->WriteHTML((string) $template, NULL);
        $mpdf->Output($filename, 'I');
    }


}

