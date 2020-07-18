<?php

declare(strict_types=1);

namespace Model;

use Skautis\Skautis;

class AuthService
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * vrací přihlašovací url
     */
    public function getLoginUrl(?string $backlink) : string
    {
        return $this->skautis->getLoginUrl($backlink);
    }

    /**
     * nastavuje základní udaje po prihlášení do SkautISu
     */
    public function setInit(string $token, int $roleId, int $unitId) : void
    {
        $this->skautis->getUser()->setLoginData($token, $roleId, $unitId);
    }

    /**
     * vrací url pro odhlášení
     */
    public function getLogoutUrl() : string
    {
        return $this->skautis->getLogoutUrl();
    }
}
