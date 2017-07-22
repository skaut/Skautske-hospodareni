<?php

namespace Model;

use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class AuthService
{

    /** @var Skautis */
    private $skautis;


    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }


    /**
     * vrací přihlašovací url
     * @param string $backlink
     * @return string
     */
    public function getLoginUrl($backlink)
    {
        return $this->skautis->getLoginUrl($backlink);
    }

    /**
     * nastavuje základní udaje po prihlášení do SkautISu
     * @param array $arr
     */
    public function setInit(array $arr): void
    {
        $this->skautis->getUser()->setLoginData($arr['token'], $arr['roleId'], $arr['unitId']);
    }

    /**
     * vrací url pro odhlášení
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->skautis->getLogoutUrl();
    }

    /**
     * prodlouží dobu přihlášení do skautisu
     */
    public function updateLogoutTime(): void
    {
        $this->skautis->updateLogoutTime();
    }

}
