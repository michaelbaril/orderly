<?php

namespace Baril\Orderly\Mixins;

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @mixin QueryBuilder
 */
class Builder
{
    public function orderByValues()
    {
        return function ($expression, array $values): QueryBuilder {
            /** @var QueryBuilder $this */
            $cases = array_combine($values, range(1, count($values)));
            $case = $this->getGrammar()->compileCase($expression, $cases, count($values) + 1);
            return $this->orderByRaw($case);
        };
    }

    public function updateColumnWithRowNumber()
    {
        return function (string $column): int {
            /** @var QueryBuilder $this */

            $connection = $this->getConnection();
            $grammar = $this->getGrammar();

            return $connection->transaction(function () use ($column, $connection, $grammar) {
                if ($grammar->supportsSequences()) {
                    $connection->statement($grammar->compileCreateSequence('rownum'));
                    $update = $this->update([$column => $connection->raw($grammar->compileNextVal('rownum'))]);
                    $connection->statement($grammar->compileDropSequence('rownum'));
                    return $update;
                } else {
                    $rownum = 0;
                    return $this->cursor()->map(function ($row) use ($column, &$rownum) {
                        return $this->clone()->where((array) $row)->limit(1)->update([
                            $column => ++$rownum,
                        ]);
                    })->sum();
                }
            });
        };
    }
}
