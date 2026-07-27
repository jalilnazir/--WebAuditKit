<?php

declare(strict_types=1);

namespace WebAuditKit\Security;

use InvalidArgumentException;

final class UrlGuard
{
    /**
     * Validate that a URL is safe for a server-side HTTP request.
     */
    public function assertSafe(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
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

        $host = (string) parse_url($url, PHP_URL_HOST);

        if ($host === '') {
            throw new InvalidArgumentException(
                'URL must contain a valid host.'
            );
        }

        $normalizedHost = strtolower(
            rtrim($host, '.')
        );

        if (
            $normalizedHost === 'localhost' ||
            str_ends_with($normalizedHost, '.localhost')
        ) {
            throw new InvalidArgumentException(
                'Localhost URLs are not allowed.'
            );
        }

        $addresses = $this->resolveHost($normalizedHost);

        if ($addresses === []) {
            throw new InvalidArgumentException(
                'Unable to resolve URL host.'
            );
        }

        foreach ($addresses as $address) {
            if (!$this->isPublicIp($address)) {
                throw new InvalidArgumentException(
                    'URLs resolving to private or reserved IP addresses are not allowed.'
                );
            }
        }
    }

    /**
     * Resolve all available IPv4 and IPv6 addresses.
     *
     * @return array<int, string>
     */
    private function resolveHost(string $host): array
    {
        /*
         * If the host itself is already an IP address,
         * DNS resolution is unnecessary.
         */
        if (
            filter_var(
                $host,
                FILTER_VALIDATE_IP
            ) !== false
        ) {
            return [$host];
        }

        $addresses = [];

        $records = dns_get_record(
            $host,
            DNS_A | DNS_AAAA
        );

        if ($records === false) {
            return [];
        }

        foreach ($records as $record) {
            if (
                isset($record['ip']) &&
                is_string($record['ip'])
            ) {
                $addresses[] = $record['ip'];
            }

            if (
                isset($record['ipv6']) &&
                is_string($record['ipv6'])
            ) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(
            array_unique($addresses)
        );
    }

    /**
     * Determine whether an IP address is publicly routable.
     */
    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
