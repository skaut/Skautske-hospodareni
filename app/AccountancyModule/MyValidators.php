<?php

/**
 *
 * @author Sin
 */
class MyValidators
{
    public static function isValidDate($control /*, $arg]*/)
    {
        return $control->value === NULL ? FALSE : TRUE;
    }
}
