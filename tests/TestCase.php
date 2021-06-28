<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\OrderlyServiceProvider;
use Dotenv\Dotenv;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // We could be using any version of Dotenv from 2.x to 5.x:
        if (method_exists(Dotenv::class, 'createImmutable')) {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        } elseif (method_exists(Dotenv::class, 'create')) {
            $dotenv = Dotenv::create(dirname(__DIR__));
        } else {
            $dotenv = new Dotenv(dirname(__DIR__));
        }
        $dotenv->load();
        $app['config']->set('database.default', 'smoothie');
        $app['config']->set('database.connections.smoothie', [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [ OrderlyServiceProvider::class ];
    }

    protected function setUp() : void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->withFactories(__DIR__ . '/database/factories');
        \DB::enableQueryLog();
    }

    protected function dumpQueryLog()
    {
        dump(\DB::getQueryLog());
    }

    protected function getLaravelMajorVersion()
    {
        return (int) app()->version();
    }
}
