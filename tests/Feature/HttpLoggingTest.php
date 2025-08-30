<?php

namespace Tests\Feature;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpLoggingTest extends TestCase
{
    /** @test */
    public function it_logs_successful_http_requests()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response(['users' => []], 200, ['Content-Type' => 'application/json'])
        ]);

        Http::get('https://api.example.com/users');

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'status' => 200,
        ]);

        $log = HttpLog::first();
        $this->assertEquals('https://api.example.com/users', $log->url);
        $this->assertEquals('GET', $log->method);
        $this->assertEquals(200, $log->status);
        $this->assertEquals(['users' => []], $log->response_body);
    }

    /** @test */
    public function it_logs_post_requests_with_body()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response(['id' => 1, 'name' => 'John'], 201, ['Content-Type' => 'application/json'])
        ]);

        Http::post('https://api.example.com/users', [
            'name' => 'John',
            'email' => 'john@example.com'
        ]);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/users',
            'method' => 'POST',
            'status' => 201,
        ]);

        $log = HttpLog::first();
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $log->request_body);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $log->response_body);
    }

    /** @test */
    public function it_logs_requests_with_headers()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response(['data' => 'success'], 200)
        ]);

        Http::withHeaders([
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json'
        ])->get('https://api.example.com/users');

        $log = HttpLog::first();
        $this->assertArrayHasKey('Authorization', $log->request_headers);
        $this->assertArrayHasKey('Content-Type', $log->request_headers);
    }

    /** @test */
    public function it_obfuscates_sensitive_data_in_request_body()
    {
        Http::fake([
            'https://api.example.com/login' => Http::response(['token' => 'abc123'], 200)
        ]);

        Http::post('https://api.example.com/login', [
            'username' => 'john',
            'password' => 'secret123'
        ]);

        $log = HttpLog::first();
        $this->assertEquals('john', $log->request_body['username']);
        $this->assertEquals('🫣', $log->request_body['password']);
    }

    /** @test */
    public function it_does_not_log_excluded_urls()
    {
        config(['spy.exclude_urls' => ['health-check', 'metrics']]);

        Http::fake([
            'https://api.example.com/health-check' => Http::response(['status' => 'ok'], 200),
            'https://api.example.com/users' => Http::response(['users' => []], 200)
        ]);

        Http::get('https://api.example.com/health-check');
        Http::get('https://api.example.com/users');

        $this->assertDatabaseMissing('http_logs', [
            'url' => 'https://api.example.com/health-check'
        ]);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/users'
        ]);
    }

    /** @test */
    public function it_does_not_log_when_disabled()
    {
        config(['spy.enabled' => false]);

        Http::fake([
            'https://api.example.com/users' => Http::response(['users' => []], 200)
        ]);

        Http::get('https://api.example.com/users');

        $this->assertDatabaseMissing('http_logs', [
            'url' => 'https://api.example.com/users'
        ]);
    }

    /** @test */
    public function it_logs_failed_requests()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response(['error' => 'Not found'], 404)
        ]);

        Http::get('https://api.example.com/users');

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'status' => 404,
        ]);

        $log = HttpLog::first();
        $this->assertEquals(['error' => 'Not found'], $log->response_body);
    }

    /** @test */
    public function it_logs_requests_with_different_http_methods()
    {
        Http::fake([
            'https://api.example.com/users/*' => Http::response(['success' => true], 200)
        ]);

        Http::put('https://api.example.com/users/1', ['name' => 'Updated']);
        Http::patch('https://api.example.com/users/1', ['email' => 'new@example.com']);
        Http::delete('https://api.example.com/users/1');

        $this->assertDatabaseHas('http_logs', ['method' => 'PUT']);
        $this->assertDatabaseHas('http_logs', ['method' => 'PATCH']);
        $this->assertDatabaseHas('http_logs', ['method' => 'DELETE']);
    }

    /** @test */
    public function it_logs_response_headers()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response(['data' => 'test'], 200, [
                'Content-Type' => 'application/json',
                'X-Rate-Limit' => '100'
            ])
        ]);

        Http::get('https://api.example.com/users');

        $log = HttpLog::first();
        $this->assertArrayHasKey('Content-Type', $log->response_headers);
        $this->assertArrayHasKey('X-Rate-Limit', $log->response_headers);
    }
}
