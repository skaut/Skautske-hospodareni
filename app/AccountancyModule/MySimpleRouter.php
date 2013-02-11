<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author sinacek
 */
class MySimpleRouter extends SimpleRouter {

    //upravuje nastavení SSL
    public function constructUrl(PresenterRequest $appRequest, Url $refUrl) {
        $url = parent::constructUrl($appRequest, $refUrl);
        if (!Environment::getVariable("ssl", false) && preg_match("/^https(.*)/", $url, $matches)) {
            $url = "http" . $matches[1];
        }
        return $url;
    }

}
