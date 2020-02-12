<?php

namespace Baril\Orderable;

use Baril\Orderable\Console\FixPositionsCommand;
use Illuminate\Support\ServiceProvider;

class OrderableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            FixPositionsCommand::class,
        ]);
    }
}
