<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebAuditKit\Security\UrlGuard;

final class UrlGuardTest extends TestCase
{
    private UrlGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new UrlGuard();
    }

    public function testRejectsInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->guard->assertSafe('not-a-url');
    }

    public function testRejectsUnsupportedScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->guard->assertSafe(
            'ftp://example.com/file.txt'
        );
    }

    #[DataProvider('unsafeUrlProvider')]
    public function testRejectsUnsafeAddresses(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->guard->assertSafe($url);
    }

    public static function unsafeUrlProvider(): array
    {
        return [
            'localhost' => [
                'http://localhost',
            ],

            'localhost subdomain' => [
                'http://test.localhost',
            ],

            'IPv4 loopback' => [
                'http://127.0.0.1',
            ],

            'private 10 network' => [
                'http://10.0.0.1',
            ],

            'private 172 network' => [
                'http://172.16.0.1',
            ],

            'private 192 network' => [
                'http://192.168.1.1',
            ],

            'link local metadata address' => [
                'http://169.254.169.254',
            ],

            'IPv6 loopback' => [
                'http://[::1]',
            ],
        ];
    }

    public function testAcceptsPublicIpv4Address(): void
    {
        $this->guard->assertSafe(
            'https://1.1.1.1'
        );

        self::assertTrue(true);
    }
}
