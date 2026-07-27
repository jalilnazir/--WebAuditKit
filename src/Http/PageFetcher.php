<?php

declare(strict_types=1);

namespace WebAuditKit\Http;

use InvalidArgumentException;
use RuntimeException;

final class PageFetcher
{
    private int $timeout;
    private int $maxBytes;

    public function __construct(
        int $timeout = 15,
        int $maxBytes = 5_000_000
    ) {
        if ($timeout < 1) {
            throw new InvalidArgumentException(
                'Timeout must be greater than zero.'
            );
        }

        if ($maxBytes < 1) {
            throw new InvalidArgumentException(
                'Maximum response size must be greater than zero.'
            );
        }

        $this->timeout = $timeout;
        $this->maxBytes = $maxBytes;
    }

    public function fetch(string $url): string
    {
        $this->validateUrl($url);

        if (!function_exists('curl_init')) {
            throw new RuntimeException(
                'The PHP cURL extension is required.'
            );
        }

        $handle = curl_init();

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to initialize cURL.'
            );
        }

        $body = '';
        $tooLarge = false;

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT =>
                'WebAuditKit/0.1 (+https://github.com/)',
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,

            CURLOPT_WRITEFUNCTION => function (
                $curl,
                string $data
            ) use (&$body, &$tooLarge): int {
                if (
                    strlen($body) + strlen($data)
                    > $this->maxBytes
                ) {
                    $tooLarge = true;

                    return 0;
                }

                $body .= $data;

                return strlen($data);
            },
        ]);

        $success = curl_exec($handle);

        $error = curl_error($handle);
        $status = (int) curl_getinfo(
            $handle,
            CURLINFO_RESPONSE_CODE
        );

        $contentType = (string) curl_getinfo(
            $handle,
            CURLINFO_CONTENT_TYPE
        );

        curl_close($handle);

        if ($tooLarge) {
            throw new RuntimeException(
                'Response exceeded the maximum allowed size.'
            );
        }

        if ($success === false) {
            throw new RuntimeException(
                'Unable to fetch URL: ' .
                ($error !== '' ? $error : 'Unknown network error.')
            );
        }

        if ($status < 200 || $status >= 400) {
            throw new RuntimeException(
                sprintf(
                    'URL returned HTTP status %d.',
                    $status
                )
            );
        }

        if (
            $contentType !== '' &&
            stripos($contentType, 'text/html') === false &&
            stripos($contentType, 'application/xhtml+xml') === false
        ) {
            throw new RuntimeException(
                'URL did not return an HTML document.'
            );
        }

        if (trim($body) === '') {
            throw new RuntimeException(
                'URL returned an empty response.'
            );
        }

        return $body;
    }

    private function validateUrl(string $url): void
    {
        if (
            filter_var($url, FILTER_VALIDATE_URL) === false
        ) {
            throw new InvalidArgumentException(
                'A valid URL is required.'
            );
        }

        $scheme = strtolower(
            (string) parse_url($url, PHP_URL_SCHEME)
        );

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(
                'Only HTTP and HTTPS URLs are supported.'
            );
        }
    }
}
