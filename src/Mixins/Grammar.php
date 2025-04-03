<?php

namespace Baril\Orderly\Mixins;

use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use LogicException;

/**
 * @mixin \Illuminate\Database\Query\Grammars\Grammar
 */
class Grammar
{
    public function compileCase()
    {
        return function ($expression, array $cases, $else = null): string {
            /** @var \Illuminate\Database\Query\Grammars\Grammar $this */
            $cases = implode(' ', array_map(function ($when, $then) {
                return "when {$this->quoteString($when)} then {$this->quoteString($then)}";
            }, array_keys($cases), array_values($cases)));
            $else = is_null($else) ? '' : "else {$this->quoteString($else)}";
            return "case {$this->wrap($expression)} $cases $else END";
        };
    }

    public function supportsSequences()
    {
        return function (): bool {
            return !($this instanceof SQLiteGrammar);
        };
    }

    public function compileCreateSequence()
    {
        return function (string $name, int $start = 1, int $increment = 1): string {
            switch (true) {
                case $this instanceof MySqlGrammar:
                    $start -= $increment;
                    return "set @$name := $start";
                case $this instanceof PostgresGrammar:
                case $this instanceof SqlServerGrammar:
                    return "create sequence {$this->wrap($name)} start with $start increment by $increment";
                default:
                    throw new LogicException('This grammar doesn\'t support sequences!');
            }
        };
    }

    public function compileNextVal()
    {
        return function (string $name, int $increment = 1): string {
            switch (true) {
                case $this instanceof MySqlGrammar:
                    return "@$name := @$name + $increment";
                case $this instanceof PostgresGrammar:
                    return "nextval({$this->quoteString($name)})";
                case $this instanceof SqlServerGrammar:
                    return "next value for {$this->wrap($name)}";
                default:
                    throw new LogicException('This grammar doesn\'t support sequences!');
            }
        };
    }

    public function compileDropSequence()
    {
        return function (string $name): string {
            switch (true) {
                case $this instanceof MySqlGrammar:
                    return 'select 1';
                case $this instanceof PostgresGrammar:
                case $this instanceof SqlServerGrammar:
                    return "drop sequence {$this->wrap($name)}";
                default:
                    throw new LogicException('This grammar doesn\'t support sequences!');
            }
        };
    }
}
