<?php

declare(strict_types=1);

namespace WebAuditKit\Http;

use InvalidArgumentException;
use RuntimeException;
use WebAuditKit\Security\UrlGuard;

final class PageFetcher
{
    private int $timeout;
    private int $maxBytes;
    private int $maxRedirects;
    private UrlGuard $urlGuard;

    public function __construct(
        int $timeout = 15,
        int $maxBytes = 5_000_000,
        ?UrlGuard $urlGuard = null,
        int $maxRedirects = 5
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

        if ($maxRedirects < 0) {
            throw new InvalidArgumentException(
                'Maximum redirects cannot be negative.'
            );
        }

        $this->timeout = $timeout;
        $this->maxBytes = $maxBytes;
        $this->maxRedirects = $maxRedirects;
        $this->urlGuard = $urlGuard ?? new UrlGuard();
    }

    /**
     * Fetch an HTML document from a public HTTP/HTTPS URL.
     */
    public function fetch(string $url): string
    {
        $currentUrl = $url;

        for (
            $redirects = 0;
            $redirects <= $this->maxRedirects;
            $redirects++
        ) {
            /*
             * Every destination is validated before connecting.
             * This includes the original URL and every redirect.
             */
            $this->urlGuard->assertSafe($currentUrl);

            $response = $this->request($currentUrl);

            if ($this->isRedirect($response['status'])) {
                if ($redirects >= $this->maxRedirects) {
                    throw new RuntimeException(
                        'Maximum redirect limit exceeded.'
                    );
                }

                $location = $response['location'];

                if ($location === null || $location === '') {
                    throw new RuntimeException(
                        'Redirect response did not contain a Location header.'
                    );
                }

                $currentUrl = $this->resolveUrl(
                    $currentUrl,
                    $location
                );

                continue;
            }

            if (
                $response['status'] < 200 ||
                $response['status'] >= 400
            ) {
                throw new RuntimeException(
                    sprintf(
                        'URL returned HTTP status %d.',
                        $response['status']
                    )
                );
            }

            $contentType = $response['content_type'];

            if (
                $contentType !== '' &&
                stripos(
                    $contentType,
                    'text/html'
                ) === false &&
                stripos(
                    $contentType,
                    'application/xhtml+xml'
                ) === false
            ) {
                throw new RuntimeException(
                    'URL did not return an HTML document.'
                );
            }

            if (trim($response['body']) === '') {
                throw new RuntimeException(
                    'URL returned an empty response.'
                );
            }

            return $response['body'];
        }

        throw new RuntimeException(
            'Unable to complete HTTP request.'
        );
    }

    /**
     * Perform one HTTP request without automatically following redirects.
     *
     * @return array{
     *     status: int,
     *     content_type: string,
     *     location: ?string,
     *     body: string
     * }
     */
    private function request(string $url): array
    {
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
        $location = null;
        $tooLarge = false;

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,

            /*
             * Redirects are intentionally handled manually so
             * every destination can pass through UrlGuard.
             */
            CURLOPT_FOLLOWLOCATION => false,

            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,

            CURLOPT_USERAGENT => 'WebAuditKit/0.1',

            CURLOPT_RETURNTRANSFER => false,

            CURLOPT_PROTOCOLS =>
                CURLPROTO_HTTP | CURLPROTO_HTTPS,

            CURLOPT_HEADERFUNCTION => function (
                $curl,
                string $header
            ) use (&$location): int {
                $length = strlen($header);

                if (
                    stripos($header, 'Location:') === 0
                ) {
                    $location = trim(
                        substr($header, 9)
                    );
                }

                return $length;
            },

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
                ($error !== ''
                    ? $error
                    : 'Unknown network error.')
            );
        }

        return [
            'status' => $status,
            'content_type' => $contentType,
            'location' => $location,
            'body' => $body,
        ];
    }

    private function isRedirect(int $status): bool
    {
        return in_array(
            $status,
            [301, 302, 303, 307, 308],
            true
        );
    }

    /**
     * Resolve an absolute or relative redirect URL.
     */
    private function resolveUrl(
        string $baseUrl,
        string $location
    ): string {
        /*
         * Already an absolute URL.
         */
        if (
            filter_var(
                $location,
                FILTER_VALIDATE_URL
            ) !== false
        ) {
            return $location;
        }

        /*
         * Scheme-relative URL.
         */
        if (str_starts_with($location, '//')) {
            $scheme = (string) parse_url(
                $baseUrl,
                PHP_URL_SCHEME
            );

            return $scheme . ':' . $location;
        }

        $scheme = (string) parse_url(
            $baseUrl,
            PHP_URL_SCHEME
        );

        $host = (string) parse_url(
            $baseUrl,
            PHP_URL_HOST
        );

        $port = parse_url(
            $baseUrl,
            PHP_URL_PORT
        );

        if ($scheme === '' || $host === '') {
            throw new RuntimeException(
                'Unable to resolve redirect URL.'
            );
        }

        $origin = $scheme . '://' . $host;

        if (is_int($port)) {
            $origin .= ':' . $port;
        }

        /*
         * Root-relative redirect.
         */
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $path = (string) parse_url(
            $baseUrl,
            PHP_URL_PATH
        );

        $directory = '/';

        if ($path !== '' && $path !== '/') {
            $directory = rtrim(
                str_replace(
                    '\\',
                    '/',
                    dirname($path)
                ),
                '/'
            ) . '/';
        }

        return $origin .
            $directory .
            $location;
    }
}
