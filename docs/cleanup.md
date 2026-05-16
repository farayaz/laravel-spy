# Cleanup and Retention

Laravel Spy stores request logs in the configured table. Use cleanup commands to keep data under control.

## Cleanup Command

```bash
# Use SPY_CLEAN_DAYS value
php artisan spy:clean

# Delete logs older than N days
php artisan spy:clean --days=30

# Delete logs older than N days and matching URL pattern
php artisan spy:clean --days=1 --url=api/users
```

## Retention Configuration

```bash
SPY_CLEAN_DAYS=30
```

## Scheduler Example

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('spy:clean')->daily();
}
```
