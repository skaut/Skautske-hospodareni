<?php

namespace CodeQuality\MandatoryVoid;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

class ReturnTypeVisitor extends NodeVisitorAbstract
{

    /** @var bool */
    private $returnsNullOrNothing = true;

    public function enterNode(Node $node)
    {
        if(!$this->returnsNullOrNothing) {
            return NULL;
        }

        if ($node->getType() === 'Expr_Yield') {
            $this->returnsNullOrNothing = false;
        } elseif ($node instanceof Return_ && $node->expr !== null) {
            $this->returnsNullOrNothing = false;
        }
    }

    public function returnsNullOrNothing() : bool
    {
        return $this->returnsNullOrNothing;
    }
}
