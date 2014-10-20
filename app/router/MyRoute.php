<?php

namespace Sinacek;

use Nette,
    Nette\Application;

/**
 *
 * @author Hána František <sinacek@gmail.com>
 */
class MyRoute extends \Nette\Application\Routers\Route {

    //upravuje nastavení SSL
    public function constructUrl(Application\Request $appRequest, Nette\Http\Url $refUrl) {
        $url = parent::constructUrl($appRequest, $refUrl);
        
        if ((!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') && preg_match("/^https(.*)/", $url, $matches)) {
            $url = "http" . $matches[1];
        }
        return $url;
    }

}
