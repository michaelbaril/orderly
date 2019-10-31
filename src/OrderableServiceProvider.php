<?php

namespace Baril\Orderable;

use Baril\Orderable\Console\FixPositionsCommand;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ServiceProvider;

class OrderableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            FixPositionsCommand::class,
        ]);

        EloquentBuilder::macro('findInOrder', function ($ids, $columns = ['*']) {
            return $this->findMany($ids, $columns)->sortByKeys($ids);
        });

        Collection::macro('sortByKeys', function(array $ids) {
            $ids = array_flip(array_values($ids));
            $i = $this->count();
            return $this->sortBy(function ($model) use ($ids, &$i) {
                return $ids[$model->getKey()] ?? ++$i;
            });
        });
    }
}
