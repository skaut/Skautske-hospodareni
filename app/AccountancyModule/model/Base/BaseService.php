<?php

/**
 * @author sinacek
 */
class BaseService extends Object {

    protected $table;
    protected $skautIS;
    private $storage;

    public function __construct() {
        $this->skautIS = new SkautisService();
        $this->storage = Environment::getSession(__CLASS__);
    }
    
    

    /**
     * je akce s $actionId editovatelná?
     * @param ID_Event $actionId
     * @return bool
     */
    public function isAccessable($actionId, $as = NULL) {
        if($as == NULL || !($as instanceof BaseService) )
            $as = new ActionService();
        try {
            $as->get($actionId);
        } catch (SkautIS_PermissionException $exc) {
            return FALSE;
        }
        return TRUE;
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

