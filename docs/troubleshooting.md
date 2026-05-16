# Troubleshooting

## No Logs Are Stored

Check these items:

1. `SPY_ENABLED=true`
2. Migrations are published and executed.
3. Database connection is valid.
4. Request URL is not excluded by `SPY_EXCLUDE_URLS`.

## Laravel Http Is Logged, Guzzle Is Not

1. Ensure `SPY_GUZZLE_ENABLED=true` for auto mode.
2. If auto mode is disabled, use `LaravelSpy::handlerStack()` in your custom `Client`.
3. If you resolve `Client::class` from container while auto mode is disabled, it is not auto-instrumented.

## Bodies Are Empty or Missing

- Body may be filtered by content-type exclusions:
  - `SPY_REQUEST_BODY_EXCLUDE_CONTENT_TYPES`
  - `SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES`
- Data may be obfuscated by `SPY_OBFUSCATES`.
- Large values may be truncated by limits:
  - `SPY_FIELD_MAX_LENGTH`
  - `SPY_FIELD_MAX_ROWS`

## Dashboard Not Accessible

1. Enable dashboard: `SPY_DASHBOARD_ENABLED=true`
2. Verify route prefix (`SPY_DASHBOARD_PREFIX`).
3. Check middleware (`SPY_DASHBOARD_MIDDLEWARE`) for auth/permission requirements.
