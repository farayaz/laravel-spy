<?php

namespace Tests;

use Farayaz\LaravelSpy\LaravelSpyServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelSpyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('spy.enabled', true);
        $app['config']->set('spy.table_name', 'http_logs');
        $app['config']->set('spy.db_connection', 'testing');
        $app['config']->set('spy.exclude_urls', []);
        $app['config']->set('spy.obfuscates', ['password', 'token']);
        $app['config']->set('spy.clean_days', 30);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('http_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('method');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('status')->nullable();
            $table->json('response_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->timestamps();
        });
    }
}
