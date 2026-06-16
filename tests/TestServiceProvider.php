<?php

namespace Baril\Orderly\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // For Laravel < v8.2.0
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model::newFactory();
        });
    }
}