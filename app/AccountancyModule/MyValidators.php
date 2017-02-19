<?php

/**
 *
 * @author Sin
 */
class MyValidators
{
    public static function isValidDate($control /*, $arg]*/) : bool
    {
        return $control->value === NULL ? FALSE : TRUE;
    }
}
