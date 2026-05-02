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
        Http::globalMiddleware(self::guzzleMiddleware());
    }

    public static function guzzleMiddleware(): callable
    {
        return static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler) {
                if (! config('spy.enabled')) {
                    return $handler($request, $options);
                }

                $startedAt = microtime(true);
                $httpLog = self::shouldLog($request) ? self::handleRequest($request) : null;

                return $handler($request, $options)->then(
                    fn (ResponseInterface $response) => self::handleResponse($response, $httpLog, $startedAt),
                    fn (Throwable $exception) => self::handleException($exception, $httpLog, $startedAt)
                );
            };
        };
    }

    public static function pushToHandlerStack(HandlerStack $stack): HandlerStack
    {
        $stack->push(self::guzzleMiddleware(), 'laravel-spy');

        return $stack;
    }

    protected static function shouldLog(RequestInterface $request): bool
    {
        return ! Str::contains((string) $request->getUri(), config('spy.exclude_urls', []));
    }

    protected static function handleRequest(RequestInterface $request): ?HttpLog
    {
        $requestBody = self::parseContent(
            'request',
            self::readStreamContent($request->getBody()),
            $request->getHeaderLine('Content-Type')
        );
        try {
            return HttpLog::create([
                'url' => urldecode(self::obfuscate($request->getUri())),
                'method' => $request->getMethod(),
                'request_headers' => self::obfuscate($request->getHeaders()),
                'request_body' => self::obfuscate($requestBody),
            ]);
        } catch (Throwable $e) {
            report($e); // silence is golden

            return null;
        }
    }

    protected static function handleResponse(ResponseInterface $response, ?HttpLog $httpLog, float $startedAt): ResponseInterface
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
                    'response_body' => self::obfuscate($responseBody),
                    'response_headers' => self::obfuscate($response->getHeaders()),
                ]);
            } catch (Throwable $e) {
                report($e); // silence is golden
            }
        }

        return $response;
    }

    protected static function handleException(Throwable $exception, ?HttpLog $httpLog, float $startedAt): void
    {
        if ($httpLog) {
            try {
                $httpLog->update([
                    'status' => 0,
                    'duration_ms' => self::calculateDurationMs($startedAt),
                    'response_body' => $exception->getMessage(),
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

    public static function obfuscate(mixed $data): mixed
    {
        $mask = config('spy.obfuscation_mask');
        $obfuscates = config('spy.obfuscates', []);
        $fieldMaxLength = config('spy.field_max_length', 10000);
        $fieldMaxRows = config('spy.field_max_rows', 1000);

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
                    $v = self::obfuscate($v);
                } elseif (is_string($v)) {
                    $v = Str::limit($v, $fieldMaxLength);
                }
            }
        } elseif (is_string($data)) {
            $data = Str::limit(str_replace($obfuscates, $mask, $data), $fieldMaxLength);
        } elseif ($data instanceof Uri) {
            parse_str($data->getQuery(), $query);

            return $data->withQuery(http_build_query(self::obfuscate($query)));
        }

        return $data;
    }
}
