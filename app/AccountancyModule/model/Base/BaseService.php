<?php

/**
 * @author sinacek
 */
class BaseService {

//    protected $user;
    protected $table;
    protected $skautIS;

    public function __construct() {
//        @deprecated
//        $this->user = Environment::getUser();
        $this->skautIS = new SkautisService();
    }

    public function isAccessable($actionId) {
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

