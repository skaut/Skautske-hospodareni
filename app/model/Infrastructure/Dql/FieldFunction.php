<?php
declare(strict_types=1);

namespace Model\Infrastructure\Dql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Kompatibilní implementace MySQL funkce FIELD() pro Doctrine ORM 2.20+.
 *
 * Usage: FIELD(field, val1, val2, ...)
 */
final class FieldFunction extends FunctionNode
{
    private $expr;
    /** @var array<int,mixed> */
    private array $vals = [];

    public function parse(Parser $parser): void
    {
        $lexer = $parser->getLexer();

        // FIELD(
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        // první výraz
        $this->expr = $parser->ArithmeticPrimary();

        // , val1, val2, ...
        do {
            $parser->match(TokenType::T_COMMA);
            $this->vals[] = $parser->ArithmeticPrimary();
        } while (! $lexer->isNextToken(TokenType::T_CLOSE_PARENTHESIS));

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $parts = [$this->expr->dispatch($sqlWalker)];
        foreach ($this->vals as $v) {
            $parts[] = $v->dispatch($sqlWalker);
        }
        return 'FIELD(' . implode(', ', $parts) . ')';
    }
}