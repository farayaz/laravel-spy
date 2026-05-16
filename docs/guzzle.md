# Guzzle Integration

Laravel Spy supports Guzzle in two modes.

## 1) Auto Mode (Default)

When enabled, the package binds `GuzzleHttp\Client` in the Laravel container with Laravel Spy handler middleware.

Important:
- Auto mode works for clients resolved from Laravel container (`app(Client::class)`).
- Auto mode does not apply to `new Client()`.
- If you use `new Client()`, you must use manual mode (`LaravelSpy::handlerStack()`).

```bash
SPY_GUZZLE_ENABLED=true
```

```php
use GuzzleHttp\Client;

$client = app(Client::class);
$client->get('https://www.google.com');
```
or
```php
public function __construct(protected Client $client) {}
public function index() {
    $this->client->get('https://www.google.com');
}
```

This will not be auto-spied, even when `SPY_GUZZLE_ENABLED=true`:

```php
use GuzzleHttp\Client;

$client = new Client();
$client->get('https://www.google.com');
```

## 2) Manual Mode

Disable auto binding and attach the spy middleware only to clients you choose.

```bash
SPY_GUZZLE_ENABLED=false
```

```php
use Farayaz\LaravelSpy\LaravelSpy;
use GuzzleHttp\Client;

// Spy enabled only on this client instance.
$client = new Client([
    'handler' => LaravelSpy::handlerStack(),
]);
$client->get('https://www.google.com?1');
```
