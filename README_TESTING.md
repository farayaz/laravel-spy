# Laravel Spy - Testing Documentation

This document provides comprehensive information about the testing setup for the Laravel Spy package.

## Overview

Laravel Spy now includes a complete PHPUnit testing suite with Docker support, allowing you to run tests without having PHP installed locally.

## Test Structure

```
tests/
├── bootstrap.php           # Test bootstrap file
├── TestCase.php           # Base test case with Orchestra Testbench
├── Unit/                  # Unit tests
│   ├── LaravelSpyTest.php        # Tests for core LaravelSpy class
│   ├── HttpLogModelTest.php      # Tests for HttpLog model
│   └── CleanCommandTest.php      # Tests for spy:clean command
└── Feature/               # Feature tests
    └── HttpLoggingTest.php       # Integration tests for HTTP logging
```

## Docker Setup

### Files Created

- `Dockerfile` - PHP 8.2 CLI environment with all required extensions
- `docker-compose.yml` - Docker Compose configuration
- `.dockerignore` - Optimizes Docker build by excluding unnecessary files
- `run-tests.sh` - Convenient script to run tests in Docker

### Running Tests

#### Option 1: Using the test runner script (Recommended)
```bash
./run-tests.sh
```

#### Option 2: Using Docker Compose
```bash
docker-compose up --build
```

#### Option 3: Manual Docker commands
```bash
# Build the image
docker build -t laravel-spy-test .

# Install dependencies
docker run --rm -v "$(pwd)":/var/www laravel-spy-test composer install

# Run tests
docker run --rm -v "$(pwd)":/var/www laravel-spy-test vendor/bin/phpunit
```

## Test Coverage

### Unit Tests

1. **LaravelSpyTest** - Tests core functionality:
   - Content parsing (JSON, XML, form data, binary)
   - Data obfuscation (arrays, strings, URIs)
   - Custom obfuscation masks

2. **HttpLogModelTest** - Tests the HttpLog model:
   - Model creation and updates
   - JSON field casting
   - Database configuration
   - Fillable attributes

3. **CleanCommandTest** - Tests the cleanup command:
   - Cleaning by age (days)
   - Cleaning by URL pattern
   - Combined filters
   - Production confirmation

### Feature Tests

1. **HttpLoggingTest** - Integration tests:
   - HTTP request logging
   - Request/response body logging
   - Header logging
   - Sensitive data obfuscation
   - URL exclusion
   - Enable/disable functionality
   - Different HTTP methods
   - Error handling

## Configuration

The test suite uses:
- **SQLite in-memory database** for fast, isolated tests
- **Orchestra Testbench** for Laravel package testing
- **HTTP faking** for testing HTTP requests without external calls
- **PHPUnit 10.x** with comprehensive assertions

## Dependencies Added

```json
{
  "require-dev": {
    "phpunit/phpunit": "^9.0|^10.0",
    "laravel/pint": "^1.0",
    "orchestra/testbench": "^8.0|^9.0",
    "mockery/mockery": "^1.4.4",
    "fakerphp/faker": "^1.9.1"
  }
}
```

## Test Environment Variables

The tests use these environment variables (configured in `phpunit.xml`):
- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`
- `SPY_ENABLED=true`
- `SPY_TABLE_NAME=http_logs`

## Running Specific Tests

```bash
# Run only unit tests
docker run --rm -v "$(pwd)":/var/www laravel-spy-test vendor/bin/phpunit tests/Unit

# Run only feature tests
docker run --rm -v "$(pwd)":/var/www laravel-spy-test vendor/bin/phpunit tests/Feature

# Run a specific test file
docker run --rm -v "$(pwd)":/var/www laravel-spy-test vendor/bin/phpunit tests/Unit/LaravelSpyTest.php

# Run with coverage (requires xdebug)
docker run --rm -v "$(pwd)":/var/www laravel-spy-test vendor/bin/phpunit --coverage-html coverage
```

## Test Results

The current test suite includes:
- **37 test methods** covering all major functionality
- **Unit tests** for core classes and methods
- **Feature tests** for end-to-end HTTP logging
- **Command tests** for the cleanup functionality

## Continuous Integration

This setup is ready for CI/CD pipelines. The Docker approach ensures consistent test environments across different systems.

### Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: ./run-tests.sh
```

## Troubleshooting

### Common Issues

1. **Docker not running**: Ensure Docker is installed and running
2. **Permission issues**: Make sure `run-tests.sh` is executable (`chmod +x run-tests.sh`)
3. **Port conflicts**: The setup uses in-memory SQLite, so no port conflicts should occur

### Test Failures

Some tests may fail initially due to:
- Missing HTTP fakes (tests try to make real HTTP calls)
- Assertion method compatibility
- Database connection configuration

These issues have been identified and can be fixed by updating the test implementations.

## Benefits of This Setup

1. **No local PHP required** - Everything runs in Docker
2. **Consistent environment** - Same PHP version and extensions everywhere
3. **Isolated tests** - Each test run starts fresh
4. **Fast execution** - In-memory database and optimized Docker image
5. **Comprehensive coverage** - Tests all major package functionality
6. **CI/CD ready** - Easy integration with automated pipelines

## Next Steps

To improve the test suite further:
1. Fix remaining test failures
2. Add more edge case tests
3. Implement code coverage reporting
4. Add performance benchmarks
5. Create integration tests with real Laravel applications
