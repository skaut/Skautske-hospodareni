<?php

namespace Extras\Sinacek;

use Nette,
    Nette\Application,
    Nette\Application\Routers\SimpleRouter;

/**
 *
 * @author sinacek
 */
class MySimpleRouter extends SimpleRouter {

    //upravuje nastavení SSL
    public function constructUrl(Application\Request $appRequest, Nette\Http\Url $refUrl) {
        $url = parent::constructUrl($appRequest, $refUrl);
        if (!Nette\Environment::getVariable("ssl", false) && preg_match("/^https(.*)/", $url, $matches)) {
            $url = "http" . $matches[1];
        }
        return $url;
    }

}
