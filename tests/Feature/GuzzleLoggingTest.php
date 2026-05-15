<?php

namespace Tests\Feature;

use Farayaz\LaravelSpy\LaravelSpy;
use Farayaz\LaravelSpy\Models\HttpLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GuzzleLoggingTest extends TestCase
{
    /** @test */
    public function it_logs_successful_guzzle_get_requests()
    {
        $client = $this->makeClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['users' => []])),
        ]);

        $client->get('https://api.example.com/users');

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'status' => 200,
        ]);

        $log = HttpLog::first();
        $this->assertEquals(['users' => []], $log->response_body);
    }

    /** @test */
    public function it_logs_guzzle_post_requests_with_obfuscated_body()
    {
        $client = $this->makeClient([
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['id' => 1])),
        ]);

        $client->post('https://api.example.com/login', [
            'json' => [
                'username' => 'john',
                'password' => 'secret123',
            ],
        ]);

        $log = HttpLog::first();
        $this->assertEquals('POST', $log->method);
        $this->assertEquals('john', $log->request_body['username']);
        $this->assertEquals('🫣', $log->request_body['password']);
        $this->assertEquals(['id' => 1], $log->response_body);
    }

    /** @test */
    public function it_does_not_log_excluded_guzzle_urls()
    {
        config(['spy.exclude_urls' => ['health-check']]);

        $client = $this->makeClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok'])),
        ]);

        $client->get('https://api.example.com/health-check');

        $this->assertDatabaseCount('http_logs', 0);
    }

    /** @test */
    public function it_does_not_log_guzzle_requests_when_disabled()
    {
        config(['spy.enabled' => false]);

        $client = $this->makeClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok'])),
        ]);

        $client->get('https://api.example.com/users');

        $this->assertDatabaseCount('http_logs', 0);
    }

    /** @test */
    public function it_logs_failed_guzzle_requests_with_status_zero()
    {
        $request = new Request('GET', 'https://api.example.com/users');

        $client = $this->makeClient([
            new ConnectException('Connection refused', $request),
        ]);

        try {
            $client->send($request);
            $this->fail('Expected ConnectException was not thrown.');
        } catch (ConnectException $exception) {
            $this->assertSame('Connection refused', $exception->getMessage());
        }

        $log = HttpLog::first();
        $this->assertEquals('https://api.example.com/users', $log->url);
        $this->assertEquals(0, $log->status);
        $this->assertSame('Connection refused', $log->response_body);
    }

    /** @test */
    public function it_builds_a_handler_stack_with_spy_middleware()
    {
        $stack = LaravelSpy::handlerStack();
        $stack->setHandler(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true])),
        ]));

        $client = new Client(['handler' => $stack]);
        $client->get('https://api.example.com/custom');

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/custom',
            'status' => 200,
        ]);
    }

    /** @test */
    public function it_resolves_guzzle_client_from_container_with_spy_middleware()
    {
        $client = app(Client::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertStringContainsString('laravel-spy', (string) $client->getConfig('handler'));
    }

    /** @test */
    public function it_does_not_log_for_plain_guzzle_clients_without_spy_handler_stack()
    {
        $client = new Client([
            'handler' => new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true])),
            ]),
        ]);

        $client->get('https://api.example.com/plain');

        $this->assertDatabaseCount('http_logs', 0);
    }

    /**
     * @param  array<int, mixed>  $queue
     */
    protected function makeClient(array $queue): Client
    {
        $stack = LaravelSpy::handlerStack();
        $stack->setHandler(new MockHandler($queue));

        return new Client(['handler' => $stack]);
    }
}
