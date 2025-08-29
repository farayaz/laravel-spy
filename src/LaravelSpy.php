<?php

namespace Farayaz\LaravelSpy;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LaravelSpy
{
    public static function boot(): void
    {
        Http::globalMiddleware(static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler) {
                if (! config('spy.enabled')) {
                    return $handler($request, $options);
                }

                $httpLog = self::shouldLog($request) ? self::handleRequest($request) : null;

                $responsePromise = $handler($request, $options);

                return $responsePromise->then(
                    fn (ResponseInterface $response) => self::handleResponse($response, $httpLog),
                    fn (\Exception $e) => self::handleException($e, $httpLog)
                );
            };
        });
    }

    protected static function shouldLog(RequestInterface $request): bool
    {
        return ! Str::contains((string) $request->getUri(), config('spy.exclude_urls', []));
    }

    protected static function handleRequest(RequestInterface $request): ?HttpLog
    {
        $requestBody = self::parseContent(
            'request',
            $request->getBody()->getContents(),
            $request->getHeaderLine('Content-Type')
        );

        return HttpLog::create([
            'url' => urldecode(self::obfuscate($request->getUri())),
            'method' => $request->getMethod(),
            'request_headers' => self::obfuscate($request->getHeaders()),
            'request_body' => self::obfuscate($requestBody),
        ]);
    }

    protected static function handleResponse(ResponseInterface $response, ?HttpLog $httpLog): ResponseInterface
    {
        if ($httpLog) {
            $responseBody = self::parseContent(
                'response',
                $response->getBody(),
                $response->getHeaderLine('Content-Type')
            );
            $httpLog->update([
                'status' => $response->getStatusCode(),
                'response_body' => self::obfuscate($responseBody),
                'response_headers' => self::obfuscate($response->getHeaders()),
            ]);
        }

        return $response;
    }

    protected static function handleException(\Exception $exception, ?HttpLog $httpLog): void
    {
        if ($httpLog) {
            $httpLog->update([
                'status' => 0,
                'response_body' => $exception->getMessage(),
            ]);
        }

        throw $exception;
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

        if (is_array($data)) {
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
                }
            }
        } elseif (is_string($data)) {
            $data = str_replace($obfuscates, $mask, $data);
        } elseif ($data instanceof \GuzzleHttp\Psr7\Uri) {
            parse_str($data->getQuery(), $query);

            return $data->withQuery(http_build_query(self::obfuscate($query)));
        }

        return $data;
    }
}
