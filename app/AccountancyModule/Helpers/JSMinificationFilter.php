<?php

namespace App\AccountancyModule\Helpers;

use JShrink\Minifier;
use WebLoader\Compiler;

class JSMinificationFilter
{

    public function __invoke(string $code, Compiler $compiler): string
    {
        return Minifier::minify($code, ['flaggedComments' => FALSE]);
    }

}
