<?php

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;

class ReturnTypeVisitor extends \PhpParser\NodeVisitorAbstract
{

    /** @var bool */
    private $returnsNullOrNothing = TRUE;

    public function enterNode(Node $node)
    {
        if(!$this->returnsNullOrNothing) {
            return NULL;
        }

        if ($node->getType() === 'Expr_Yield') {
            $this->returnsNullOrNothing = FALSE;
        } else if($node instanceof Return_ && $node->expr !== NULL) {
            $this->returnsNullOrNothing = FALSE;
        }
    }

    public function returnsNullOrNothing(): bool
    {
        return $this->returnsNullOrNothing;
    }

}
