<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebAuditKit\Http\PageFetcher;

final class PageFetcherTest extends TestCase
{
    public function testRejectsInvalidUrl(): void
    {
        $fetcher = new PageFetcher();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid URL is required.');

        $fetcher->fetch('not-a-valid-url');
    }

    public function testRejectsUnsupportedScheme(): void
    {
        $fetcher = new PageFetcher();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Only HTTP and HTTPS URLs are supported.'
        );

        $fetcher->fetch('ftp://example.com/file.html');
    }

    public function testRejectsZeroTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Timeout must be greater than zero.'
        );

        new PageFetcher(0);
    }

    public function testRejectsZeroMaximumResponseSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Maximum response size must be greater than zero.'
        );

        new PageFetcher(15, 0);
    }
}
