<?php

namespace Baril\Orderly;

use Baril\Orderly\Console\FixPositionsCommand;
use Baril\Orderly\Mixins\Builder as BuilderMixin;
use Baril\Orderly\Mixins\Grammar as GrammarMixin;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\ServiceProvider;

class OrderlyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            FixPositionsCommand::class,
        ]);
    }

    public function boot()
    {
        Builder::mixin(new BuilderMixin());
        Grammar::mixin(new GrammarMixin());
    }
}
