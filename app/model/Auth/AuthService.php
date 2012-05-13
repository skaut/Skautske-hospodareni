<?php

/**
 * @author Hána František
 */

class AuthService extends BaseService {
    
    /**
     * vrací přihlašovací url
     * @param string $backlink
     * @return string 
     */
    public function getLoginUrl($backlink){
        return $this->skautIS->getLoginUrl($backlink);
    }
    
    /**
     * nastavuje základní udaje po prihlášení do SkautISu
     * @param array $arr 
     */
    public function setInit(array $arr){
        $this->skautIS->setToken($arr['token']);
        $this->skautIS->setRoleId($arr['roleId']);
        $this->skautIS->setUnitId($arr['unitId']);
    }
    
    /**
     * vrací url pro odhlášení
     * @return string 
     */
    public function getLogoutUrl(){
        return $this->skautIS->getLogoutUrl();
    }
    
    /**
     * prodlouží dobu přihlášení do skautISu
     */
    public function updateLogoutTime(){
        $this->skautIS->updateLogoutTime();
    }
    
}