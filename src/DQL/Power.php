<?php

namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class Power extends FunctionNode
{
    private $base;
    private $exponent;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'POWER(%s, %s)',
            $this->base->dispatch($sqlWalker),
            $this->exponent->dispatch($sqlWalker)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->base = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->exponent = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
