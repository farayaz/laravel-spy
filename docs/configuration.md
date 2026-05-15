# Configuration

Laravel Spy configuration lives in `config/spy.php` after publishing:

```bash
php artisan vendor:publish --provider="Farayaz\LaravelSpy\LaravelSpyServiceProvider"
```

## Core Flags

```bash
SPY_ENABLED=true
SPY_GUZZLE_ENABLED=true
```

- `SPY_ENABLED`: master switch for Laravel Spy logging middleware.
- `SPY_GUZZLE_ENABLED`: enables container binding for `GuzzleHttp\Client` with spy handler stack.

## Database Settings

```bash
SPY_TABLE_NAME=http_logs
SPY_DB_CONNECTION=
```

- `SPY_TABLE_NAME`: table used for logs.
- `SPY_DB_CONNECTION`: optional custom connection name.

## URL Exclusions

```bash
SPY_EXCLUDE_URLS=api/health,ping,status
```

Any request URL containing one of these values will not be logged.

## Obfuscation

```bash
SPY_OBFUSCATES=password,token,api_key,secret
SPY_OBFUSCATION_MASK=***HIDDEN***
```

- `SPY_OBFUSCATES`: comma-separated keys to mask.
- `SPY_OBFUSCATION_MASK`: replacement value for sensitive fields.

## Content-Type Exclusions

```bash
SPY_REQUEST_BODY_EXCLUDE_CONTENT_TYPES=image/
SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES=video/,application/pdf
```

Use this to skip logging binary or large body content.

## Limits

```bash
SPY_FIELD_MAX_LENGTH=10000
SPY_FIELD_MAX_ROWS=10000
```

- `SPY_FIELD_MAX_LENGTH`: max characters per stored field value.
- `SPY_FIELD_MAX_ROWS`: max entries retained when serializing arrays/collections.

## Retention

```bash
SPY_CLEAN_DAYS=30
```

Used by the cleanup command default (`spy:clean`).

## Dashboard

```bash
SPY_DASHBOARD_ENABLED=false
SPY_DASHBOARD_PREFIX=spy
SPY_DASHBOARD_MIDDLEWARE=web
```

- `SPY_DASHBOARD_ENABLED`: enables dashboard routes.
- `SPY_DASHBOARD_PREFIX`: route prefix.
- `SPY_DASHBOARD_MIDDLEWARE`: comma-separated middleware list.
