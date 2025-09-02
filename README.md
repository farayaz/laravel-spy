<p align="center">
    <img src="./laravel-spy.svg" alt="Laravel Spy Logo" width="300" height="300" />
</p>

<p align="center">
    <a href="https://packagist.org/packages/farayaz/laravel-spy">
        <img src="https://img.shields.io/packagist/v/farayaz/laravel-spy.svg?style=flat-square" alt="Latest Version on Packagist" />
    </a>
    <a href="https://packagist.org/packages/farayaz/laravel-spy">
        <img src="https://img.shields.io/packagist/dt/farayaz/laravel-spy.svg?style=flat-square" alt="Total Downloads" />
    </a>
    <a href="https://packagist.org/packages/farayaz/laravel-spy">
        <img src="https://img.shields.io/packagist/l/farayaz/laravel-spy.svg?style=flat-square" alt="License" />
    </a>
</p>

# Laravel Spy

**Laravel Spy** is a lightweight Laravel package designed to track and log outgoing HTTP requests made by your Laravel application.

This package is useful for debugging, monitoring, and auditing external API calls or HTTP requests, providing developers with a zero config, simple way to inspect request details such as URLs, methods, headers, and responses.

## Features

- Tracks all outgoing HTTP requests made via Laravel's HTTP client.
- Logs request details, including URL, method, headers, payload, and response.
- Configurable logging options to customize and obfuscate sensitive data.

## Requirements

- **PHP**: ^8.1
- **Laravel**: ^10.0 | ^11.0 | ^12.0
- **Development Dependencies** (optional):
  - `laravel/pint`: ^1.0 (for code style linting)
  - `phpunit/phpunit`: ^9.0 (for running tests)

## Installation

You can install the package via Composer:

```bash
composer require farayaz/laravel-spy
```

The package uses Laravel's auto-discovery feature. After installation, the package is ready to use with its default configuration.
```bash
php artisan vendor:publish --provider="Farayaz\LaravelSpy\LaravelSpyServiceProvider"
```
```bash
php artisan migrate
```


## Usage
Once installed and configured, Laravel Spy automatically tracks all outgoing HTTP requests made using Laravel's Http facade or HTTP client. The package logs the following details for each request:
* The full URL of the request
* The HTTP method (e.g., GET, POST, PUT)
* Request Headers
* Request Body
* Response Header
* Response Body
* Response HTTP Status code

## Example:
After installing `laravel-spy` and publishing the configuration, any usage of Laravel's HTTP client (for example, in your controllers or jobs) will be automatically logged.

Laravel Spy will log the details of this outgoing request to the `http_logs` table in your database.

```php
Http::get('https://github.com/farayaz/laravel-spy/');
```

## Configuration
To customize Laravel Spy, publish the config file and edit `config/spy.php`.

### Basic Configuration

Configure these via environment variables:
```bash
SPY_ENABLED=true
```

### URL Exclusions

Exclude specific URLs from being logged via environment variable:
```bash
SPY_EXCLUDE_URLS=api/health,ping,status
```

### Data Obfuscation

Laravel Spy can obfuscate sensitive data in your logs. By default, it obfuscates `password` and `token` fields, but you can customize this via environment variables:

```bash
SPY_OBFUSCATES=password,token,api_key,secret
SPY_OBFUSCATION_MASK=***HIDDEN***
```

### Excluding Content Types from Logging

You can configure Laravel Spy to exclude specific content types from being logged for both request and response bodies. This is useful for binary data, images, videos, or other content you do not want included in logs.
```bash
SPY_REQUEST_BODY_EXCLUDE_CONTENT_TYPES=image/
SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES=video/,application/pdf
```

### Automatic Log Retention

Configure how long logs should be retained before automatic cleanup via environment variable:

```bash
SPY_CLEAN_DAYS=7  # Keep logs for 7 days (default is 30)
```

## Dashboard

Laravel Spy includes a simple built-in dashboard at `/spy` with:

```bash
SPY_DASHBOARD_ENABLED=true
SPY_DASHBOARD_MIDDLEWARE=web,auth
```

## Cleaning up logs

Laravel Spy provides a `spy:clean` command to remove old HTTP logs:

```bash
# Clean logs based on your config
php artisan spy:clean

# Clean logs older than 30 days
php artisan spy:clean --days=30

# Clean logs matching URL pattern
php artisan spy:clean --days=1 --url=api/users
```

### Automated cleanup

You can schedule automatic cleanup in your Laravel scheduler:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
  $schedule->command('spy:clean')->daily();
}
```

## Contributing
Contributions are welcome! To contribute to Laravel Spy:
* Fork the repository on GitHub.
* Clone your fork and create a new branch (git checkout -b feat-your-feature).
* Run code style checks with Laravel Pint (vendor/bin/pint).
* Commit your changes and push to your fork.
* Create a pull request with a clear description of your changes.

## Issues
If you encounter any issues or have feature requests, please open an issue on the GitHub repository. Provide as much detail as possible, including:
* Laravel version
* PHP version
* Package version
* Steps to reproduce
* Expected vs. actual behavior
* Any relevant error messages or logs

## License
Laravel Spy is open-sourced software licensed under the MIT License.

## Contact
For questions or support, reach out via the GitHub repository or open an issue.
