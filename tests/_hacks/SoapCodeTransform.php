<?php

namespace VCR\CodeTransform;

class SoapCodeTransform extends AbstractCodeTransform
{
    public const NAME = 'vcr_soap';

    protected function transformCode($code)
    {
        return $code;
    }
}
