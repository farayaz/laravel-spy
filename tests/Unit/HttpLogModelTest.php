<?php

namespace Tests\Unit;

use Farayaz\LaravelSpy\Models\HttpLog;
use Tests\TestCase;

class HttpLogModelTest extends TestCase
{
    /** @test */
    public function it_can_create_http_log()
    {
        $httpLog = HttpLog::create([
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_body' => ['name' => 'John'],
            'status' => 200,
            'response_body' => ['id' => 1, 'name' => 'John'],
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->assertInstanceOf(HttpLog::class, $httpLog);
        $this->assertEquals('https://api.example.com/users', $httpLog->url);
        $this->assertEquals('GET', $httpLog->method);
        $this->assertEquals(200, $httpLog->status);
    }

    /** @test */
    public function it_casts_json_fields_correctly()
    {
        $httpLog = HttpLog::create([
            'url' => 'https://api.example.com/users',
            'method' => 'POST',
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_body' => ['name' => 'John', 'email' => 'john@example.com'],
            'status' => 201,
            'response_body' => ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->assertIsArray($httpLog->request_headers);
        $this->assertIsArray($httpLog->request_body);
        $this->assertIsArray($httpLog->response_body);
        $this->assertIsArray($httpLog->response_headers);

        $this->assertEquals(['Content-Type' => 'application/json'], $httpLog->request_headers);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $httpLog->request_body);
        $this->assertEquals(['id' => 1, 'name' => 'John', 'email' => 'john@example.com'], $httpLog->response_body);
        $this->assertEquals(['Content-Type' => 'application/json'], $httpLog->response_headers);
    }

    /** @test */
    public function it_uses_configured_table_name()
    {
        $httpLog = new HttpLog();
        
        $this->assertEquals('http_logs', $httpLog->getTable());
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $httpLog = new HttpLog();
        
        $expectedFillable = [
            'url',
            'method',
            'request_headers',
            'request_body',
            'status',
            'response_body',
            'response_headers',
        ];

        $this->assertEquals($expectedFillable, $httpLog->getFillable());
    }

    /** @test */
    public function it_can_update_http_log()
    {
        $httpLog = HttpLog::create([
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_body' => null,
        ]);

        $httpLog->update([
            'status' => 200,
            'response_body' => ['users' => []],
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->assertEquals(200, $httpLog->status);
        $this->assertEquals(['users' => []], $httpLog->response_body);
        $this->assertEquals(['Content-Type' => 'application/json'], $httpLog->response_headers);
    }

    /** @test */
    public function it_handles_null_json_fields()
    {
        $httpLog = HttpLog::create([
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'request_headers' => null,
            'request_body' => null,
            'response_body' => null,
            'response_headers' => null,
        ]);

        $this->assertNull($httpLog->request_headers);
        $this->assertNull($httpLog->request_body);
        $this->assertNull($httpLog->response_body);
        $this->assertNull($httpLog->response_headers);
    }
}
