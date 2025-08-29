# Laravel Spy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/farayaz/laravel-spy.svg?style=flat-square)](https://packagist.org/packages/farayaz/laravel-spy)
[![Total Downloads](https://img.shields.io/packagist/dt/farayaz/laravel-spy.svg?style=flat-square)](https://packagist.org/packages/farayaz/laravel-spy)
[![License](https://img.shields.io/packagist/l/farayaz/laravel-spy.svg?style=flat-square)](https://packagist.org/packages/farayaz/laravel-spy)

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

By Default `Laravel-Spy` logs all HTTP requests and API calls. You can customize it to exclude specific URLs or obfuscate sensitive data.


## Configuration
To customize the behavior of Laravel Spy, you can publish the configuration file:
```bash
php artisan vendor:publish --provider="Farayaz\LaravelSpy\LaravelSpyServiceProvider"
```
```bash
php artisan migrate
```
This will create a `config/spy.php` file where you can configure the following options:

```
    'table_name' => 'http_logs',

    'enabled' => env('SPY_ENABLED', true),
    'db_connection' => env('SPY_DB_CONNECTION'),

    'exclude_urls' => explode(',', env('SPY_EXCLUDE_URLS', '')),
    'obfuscates' => explode(',', env('SPY_OBFUSCATES', 'password')),

    'request_body_exclude_content_types' => [
        // 'video/',
        // 'audio/',
        // 'application/pdf',
        // 'application/zip',
        // 'application/x-zip-compressed',
        // 'application/octet-stream',
        // 'multipart/form-data',
    ],

    'request_body_exclude_content_types' => [
        // 'video/',
        // 'audio/',
        // 'application/pdf',
        // 'application/zip',
        // 'application/x-zip-compressed',
        // 'application/octet-stream',
        // 'multipart/form-data',
    ],
```

### Excluding Content Types from Logging

You can configure Laravel Spy to exclude specific content types from being logged for both request and response bodies. This is useful for binary data, images, videos, or other content you do not want included in logs.

To enable this, you can configure the following options on `config/spy.php`:

```php
'request_body_exclude_content_types' => [
    'image/', // 'image/' will exclude any image-type logging in request bodies such as 'image/png', 'image/jpeg', and so on
],

'response_body_exclude_content_types' => [
    'audio/', // 'audio/' will exclude any audio-type logging in response bodies such as 'audio/mp3', 'audio/wav', and so on
],
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
Once you’ve installed `Laravel-Spy` via Composer and published the configuration, open your `web.php` file and add the following line to start logging results into the `http_logs` table in your database:

```php
Route::get("/spy", function () {
    Http::get('https://github.com/farayaz/laravel-spy/');
});
```
Now head to the `http_logs` table to view the logged parameters.

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







