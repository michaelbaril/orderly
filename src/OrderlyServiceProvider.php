<?php

namespace Baril\Orderly;

use Baril\Orderly\Console\FixPositionsCommand;
use Illuminate\Support\ServiceProvider;

class OrderlyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            FixPositionsCommand::class,
        ]);
    }
}
