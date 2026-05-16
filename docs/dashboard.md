# Dashboard

Laravel Spy includes a lightweight dashboard for browsing HTTP logs.

## Enable Dashboard

```bash
SPY_DASHBOARD_ENABLED=true
SPY_DASHBOARD_PREFIX=spy
SPY_DASHBOARD_MIDDLEWARE=web,auth
```

By default, dashboard is accessible at:

- `/spy`

## Publish Dashboard Views (Optional)

```bash
php artisan vendor:publish --tag=spy-views
```

This copies views to your app so you can customize the UI.
