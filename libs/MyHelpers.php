<?php
abstract class MyHelpers extends Object {
    public static function newsResources($string) {
        $foo = explode("news_", $string, 2);
        return $foo[1];
    }
}
