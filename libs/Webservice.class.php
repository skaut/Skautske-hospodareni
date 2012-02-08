<?php

class WebService extends SoapClient {

    private static $times = array();
    private $name;
    private $init;

    //const EXCEPT = 'Nepodařilo se připojit na databázi. Zkuste to později.';

    public function __construct($wsdl, array $init, $name = "default", $compression = TRUE) {
        $this->init = $init;
//        try {
        if (!isset($wsdl))
            throw new Exception();
        $name = "default";
        $this->name = $name;
        self::$times[$name] = 0;
        //$this->startTimer();
        Stopwatch::start();
        $soapOpts['encoding'] = 'utf-8';
        if ($compression === TRUE)
            $soapOpts['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
//            if (isset($user, $pass)) {
//                $soapOpts['login'] = $user;
//                $soapOpts['password'] = $pass;
//            }
        parent::__construct($wsdl, $soapOpts);
        //$this->stopTimer();
        Stopwatch::stop($name);
//        } catch (Exception $e) {
//            Debugger::dump($e);
//            throw $e;
//            //throw new Exception(self::EXCEPT);
//        }
    }
    
    public function __call($function_name, $arguments) {
        return $this->__soapCall($function_name, $arguments);
    }

    public function __soapCall($function_name, $arguments, $options = null , $input_headers = null, &$output_headers = null ) {
        $fname = ucfirst($function_name);
        $args = array_merge($this->init, $arguments[0]);
        if (isset($arguments[1]) && $arguments[1] !== null)
            $args = array(array($arguments[1] => $args));
        else {
            $function_name = strtolower(substr($function_name, 0, 1)) . substr($function_name, 1); //nahrazuje lcfirst
            $args = array(array($function_name . "Input" => $args));
        }

        try {
            Stopwatch::start();
            $ret = parent::__call($fname, $args);
            Stopwatch::stop("WS-" . $function_name);
            $ret = $this->toArrayHash($ret);
            if (isset($ret->{$fname . "Result"})) {
                return $ret->{$fname . "Result"};
            }
            return $ret;
        } catch (SoapFault $e) {
            Debugger::log($e->getMessage());
            //dump($e->getMessage());
            //throw new Exception(self::EXCEPT);
        }
    }
    
    /**
     * prevede object na ArrayHash
     * @param mixed $obj 
     */
    function toArrayHash($obj){
        $obj = ArrayHash::from((array)$obj);
        foreach ($obj as $key => $value) {
            if($value instanceof stdClass){
                $obj[$key] = $this->toArrayHash($value);
            }
        }
        return $obj;
    }

}
