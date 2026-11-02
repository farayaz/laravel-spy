# Dashboard

Laravel Spy includes a lightweight dashboard for browsing HTTP logs.

## Enable Dashboard

```bash
SPY_DASHBOARD_ENABLED=true
SPY_DASHBOARD_MIDDLEWARE=web,auth,admin
```

Replace `admin` with your application's administrator middleware alias. Apply
administrator access to the whole dashboard because URLs, headers, and bodies
can contain sensitive data. Every dashboard route uses this middleware list.

By default, dashboard is accessible at:

- `/spy`

The dashboard has two pages: `/spy` for statistics and charts and `/spy/logs`
for HTTP logs. The previous `/spy/analytics` URL also opens Analytics.
Filter logs by URL, method, status group, or minimum duration. The selected period
also applies to the list. Choose 10, 20, or 50 rows per page and sort by log time.
Click a URL to inspect its stored request and response headers and bodies in a
side panel without losing your place in the table.
Select **All time** to include logs older than 30 days.
The chart script is bundled with the package and served from a versioned dashboard URL,
so the dashboard does not need access to a JavaScript CDN or web font service.

## Publish Dashboard Views (Optional)

```bash
php artisan vendor:publish --tag=spy-views
```

This copies views to your app so you can customize the UI.
