<?php

/**
 * @author sinacek
 */
class BaseService {

    protected $user;
    protected $table;
    protected $skautIS;
    /**
     * pole promenych, ktere se mají persistentne uchovávat
     * @var array
     */
    protected $persistenceVars;

    public function __construct() {
        $this->user = Environment::getUser();
        $this->skautIS = SkautIS::getInstance();
    }

    /**
     * ulozi promenne z persistenceVars do Session namespace
     * @param Session $ns
     * @return null
     * @deprecated
     */
    protected function saveVars(&$ns) {
        if (is_null($this->persistenceVars))
            return;
        if (!is_array($this->persistenceVars))
            throw new InvalidArgumentException("persistenceVars must be array " . gettype($this->persistenceVars) . "given.");
        foreach ($this->persistenceVars as $var) {
            $ns->{$var} = serialize($this->{$var});
        }
    }

    /**
     * nacte promenne z Session namespace do persistenceVars
     * @param Session $ns
     * @return null
     * @deprecated
     */
    protected function loadVars(&$ns) {
        if (is_null($this->persistenceVars))
            return;
        if (!is_array($this->persistenceVars))
            throw new InvalidArgumentException("persistenceVars must be array" . gettype($this->persistenceVars) . "given.");
        $objVar = array_keys(get_object_vars($this));
        foreach ($this->persistenceVars as $var) {
            if (!in_array($var, $objVar))
                throw new InvalidArgumentException("Proměnná \"$var\" není deklarována at ". __CLASS__);
            $this->{$var} = $ns->{$var} ? unserialize($ns->{$var}) : NULL;
        }
    }
    
    /**
     * vrátí pdf do prohlizece
     * @param type $template
     * @param type $filename
     * @return type 
     */
    function makePdf($template = NULL, $filename = NULL){
        if($template === NULL)
            return FALSE;
        define('_MPDF_PATH', LIBS_DIR . '/mpdf/');
        require_once(_MPDF_PATH . 'mpdf.php');
        $mpdf = new mPDF('utf-8');
        $mpdf->WriteHTML((string)$template, NULL);
        $mpdf->Output($filename, 'I');
    }

}

