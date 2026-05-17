<?php

namespace Farayaz\LaravelSpy;

use Farayaz\LaravelSpy\Models\HttpLog;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

class LaravelSpy
{
    public static function boot(): void
    {
        Http::globalMiddleware(self::middleware());
    }

    public static function middleware(): callable
    {
        return static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler) {
                if (! config('spy.enabled')) {
                    return $handler($request, $options);
                }

                $requestUri = $request->getUri();
                $startedAt = microtime(true);
                $httpLog = self::shouldLog($request) ? self::handleRequest($requestUri, $request) : null;

                return $handler($request, $options)->then(
                    fn (ResponseInterface $response) => self::handleResponse($requestUri, $response, $httpLog, $startedAt),
                    fn (Throwable $exception) => self::handleException($requestUri, $exception, $httpLog, $startedAt)
                );
            };
        };
    }

    public static function handlerStack(): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push(self::middleware(), 'laravel-spy');

        return $stack;
    }

    protected static function shouldLog(RequestInterface $request): bool
    {
        return ! Str::contains((string) $request->getUri(), config('spy.exclude_urls', []));
    }

    protected static function handleRequest(Uri $uri, RequestInterface $request): ?HttpLog
    {
        $requestBody = self::parseContent(
            'request',
            self::readStreamContent($request->getBody()),
            $request->getHeaderLine('Content-Type')
        );
        try {
            return HttpLog::create([
                'url' => urldecode((string) self::obfuscate($uri)),
                'method' => $request->getMethod(),
                'request_headers' => self::obfuscate($request->getHeaders(), $uri),
                'request_body' => self::obfuscate($requestBody, $uri),
            ]);
        } catch (Throwable $e) {
            report($e); // silence is golden

            return null;
        }
    }

    protected static function handleResponse(Uri $uri, ResponseInterface $response, ?HttpLog $httpLog, float $startedAt): ResponseInterface
    {
        if ($httpLog) {
            try {
                $responseBody = self::parseContent(
                    'response',
                    $response->getBody(),
                    $response->getHeaderLine('Content-Type')
                );
                $httpLog->update([
                    'status' => $response->getStatusCode(),
                    'duration_ms' => self::calculateDurationMs($startedAt),
                    'response_body' => self::obfuscate($responseBody, $uri),
                    'response_headers' => self::obfuscate($response->getHeaders(), $uri),
                ]);
            } catch (Throwable $e) {
                report($e); // silence is golden
            }
        }

        return $response;
    }

    protected static function handleException(Uri $uri, Throwable $exception, ?HttpLog $httpLog, float $startedAt): void
    {
        if ($httpLog) {
            try {
                $httpLog->update([
                    'status' => 0,
                    'duration_ms' => self::calculateDurationMs($startedAt),
                    'response_body' => self::obfuscate($exception->getMessage(), $uri),
                ]);
            } catch (Throwable $e) {
                report($e); // silence is golden
            }
        }

        throw $exception;
    }

    protected static function calculateDurationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    protected static function readStreamContent(StreamInterface $stream): string
    {
        if (! $stream->isSeekable()) {
            return $stream->getContents();
        }

        $position = $stream->tell();
        $stream->rewind();
        $content = $stream->getContents();
        $stream->seek($position);

        return $content;
    }

    public static function parseContent(string $context, mixed $content, ?string $contentType = null): mixed
    {
        if (empty($content)) {
            return null;
        }

        $excludeTypes = config('spy.' . $context . '_body_exclude_content_types', []);
        if (! empty($contentType)) {
            foreach ($excludeTypes as $excludeType) {
                if (str_contains($contentType, $excludeType)) {
                    return ['content excluded by configuration'];
                }
            }
        }

        if (str_contains($contentType, 'application/json') || json_decode($content, true) !== null) {
            return json_decode($content, true);
        }

        if (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) {
            return json_decode(json_encode(simplexml_load_string($content)), true);
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($content, $data);

            return $data;
        }

        if (str_contains($contentType, 'multipart/form-data')) {
            return base64_encode($content);
        }

        if (($contentType && (
            str_contains($contentType, 'image/') ||
            str_contains($contentType, 'video/') ||
            str_contains($contentType, 'application/') ||
            str_contains($contentType, 'audio/')
        ))) {
            return base64_encode($content);
        }

        return $content;
    }

    public static function obfuscate(mixed $data, ?Uri $uri = null): mixed
    {
        $mask = config('spy.obfuscation_mask');
        $obfuscates = self::obfuscationKeys($uri);
        $fieldMaxLength = config('spy.field_max_length', 10000);
        $fieldMaxRows = config('spy.field_max_rows', 10000);

        if (is_array($data)) {
            if ($fieldMaxRows && count($data) > $fieldMaxRows) {
                $data = array_slice($data, 0, $fieldMaxRows, true);
                $data['_spy_truncated'] = true;
            }

            foreach ($data as $k => &$v) {
                foreach ($obfuscates as $key) {
                    if (strcasecmp($k, $key) === 0) {
                        if (is_array($v)) {
                            foreach ($v as &$item) {
                                $item = $mask;
                            }
                        } else {
                            $v = $mask;
                        }
                    }
                }

                if (is_array($v)) {
                    $v = self::obfuscate($v, $uri);
                } elseif (is_string($v)) {
                    $v = Str::limit($v, $fieldMaxLength);
                }
            }
        } elseif (is_string($data)) {
            $data = Str::limit(str_replace($obfuscates, $mask, $data), $fieldMaxLength);
        } elseif ($data instanceof Uri) {
            parse_str($data->getQuery(), $query);

            $uri = $uri ?? $data;

            return $data->withQuery(http_build_query(self::obfuscate($query, $uri)));
        }

        return $data;
    }

    protected static function obfuscationKeys(?Uri $uri): array
    {
        $rules = config('spy.obfuscates', []);
        $keys = $rules['*'] ?? [];

        if (! is_array($keys)) {
            $keys = [$keys];
        }

        if ($uri === null) {
            return array_values(array_unique(array_filter($keys, fn ($value) => is_string($value) && $value !== '')));
        }

        $domain = $uri->getHost();
        if ($domain !== '' && array_key_exists($domain, $rules)) {
            $domainKeys = $rules[$domain];
            if (! is_array($domainKeys)) {
                $domainKeys = [$domainKeys];
            }
            $keys = array_merge($keys, $domainKeys);
        }

        return array_values(array_unique(array_filter($keys, fn ($value) => is_string($value) && $value !== '')));
    }
}
