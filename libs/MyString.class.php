<?php
class MyString extends Object {
    static function filesWebalize($s="") {
        return String::webalize($s, "_", FALSE);
    }

    static function detect($s) {
        if (preg_match('#[\x80-\x{1FF}\x{2000}-\x{3FFF}]#u', $s))
            return 'UTF-8';

        if (preg_match('#[\x7F-\x9F\xBC]#', $s))
            return 'WINDOWS-1250';

        return 'ISO-8859-2';
    }

}
