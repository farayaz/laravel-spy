<?php

namespace Tests\Unit;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HttpLogConnectionTest extends TestCase
{
    /** @test */
    public function it_uses_the_configured_db_connection_key()
    {
        config()->set('database.connections.spy_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        Schema::connection('spy_testing')->create('spy_http_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('method');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('response_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->timestamps();
        });

        config()->set('spy.db_connection', 'spy_testing');
        config()->set('spy.table_name', 'spy_http_logs');

        $log = HttpLog::create([
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
        ]);

        $this->assertSame('spy_testing', $log->getConnectionName());
        $this->assertDatabaseHas('spy_http_logs', [
            'url' => 'https://api.example.com/users',
        ], 'spy_testing');
    }
}
