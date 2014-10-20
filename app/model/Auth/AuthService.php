<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class AuthService extends BaseService {

    /**
     * vrací přihlašovací url
     * @param string $backlink
     * @return string 
     */
    public function getLoginUrl($backlink) {
        return $this->skautis->getLoginUrl($backlink);
    }

    /**
     * nastavuje základní udaje po prihlášení do SkautISu
     * @param array $arr 
     */
    public function setInit(array $arr) {
        $this->skautis->setToken($arr['token']);
        $this->skautis->setRoleId($arr['roleId']);
        $this->skautis->setUnitId($arr['unitId']);
    }

    /**
     * vrací url pro odhlášení
     * @return string 
     */
    public function getLogoutUrl() {
        return $this->skautis->getLogoutUrl();
    }

    /**
     * prodlouží dobu přihlášení do skautisu
     */
    public function updateLogoutTime() {
        $this->skautis->updateLogoutTime();
    }

}