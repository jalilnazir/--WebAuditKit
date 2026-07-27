<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Result\AuditResult;
use WebAuditKit\WebAuditKit;

final class WebAuditKitTest extends TestCase
{
    public function testCanAuditHtml(): void
    {
        $kit = new WebAuditKit();

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Example Website</title>
    <meta
        name="description"
        content="Example website description."
    >
</head>
<body>
    <h1>Example Website</h1>
    <p>This is a test page.</p>
</body>
</html>
HTML;

        $result = $kit->auditHtml(
            $html,
            'https://example.com'
        );

        self::assertInstanceOf(
            AuditResult::class,
            $result
        );

        self::assertSame(
            'https://example.com',
            $result->url()
        );

        self::assertNotEmpty(
            $result->checks()
        );

        self::assertTrue(
            $result->hasCheck('title')
        );

        self::assertTrue(
            $result->hasCheck('meta_description')
        );

        self::assertTrue(
            $result->hasCheck('headings')
        );

        self::assertTrue(
            $result->hasCheck('canonical')
        );

        self::assertTrue(
            $result->hasCheck('robots')
        );
    }

    public function testAuditHtmlAcceptsHtmlWithoutUrl(): void
    {
        $kit = new WebAuditKit();

        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>Hello</h1>
</body>
</html>
HTML;

        $result = $kit->auditHtml($html);

        self::assertInstanceOf(
            AuditResult::class,
            $result
        );

        self::assertNull(
            $result->url()
        );

        self::assertNotEmpty(
            $result->checks()
        );

        self::assertTrue(
            $result->hasCheck('title')
        );

        self::assertTrue(
            $result->hasCheck('headings')
        );
    }

    public function testAuditHtmlReturnsStructuredResult(): void
    {
        $kit = new WebAuditKit();

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Complete Website SEO Audit Guide</title>
    <meta
        name="description"
        content="A complete guide to auditing websites for technical SEO and on-page SEO issues."
    >
    <meta
        name="robots"
        content="index, follow"
    >
    <link
        rel="canonical"
        href="https://example.com"
    >
</head>
<body>
    <h1>Complete Website SEO Audit Guide</h1>
</body>
</html>
HTML;

        $result = $kit->auditHtml(
            $html,
            'https://example.com'
        );

        $array = $result->toArray();

        self::assertSame(
            'https://example.com',
            $array['url']
        );

        self::assertArrayHasKey(
            'status',
            $array
        );

        self::assertArrayHasKey(
            'summary',
            $array
        );

        self::assertArrayHasKey(
            'checks',
            $array
        );

        self::assertArrayHasKey(
            'title',
            $array['checks']
        );

        self::assertArrayHasKey(
            'meta_description',
            $array['checks']
        );

        self::assertArrayHasKey(
            'headings',
            $array['checks']
        );

        self::assertArrayHasKey(
            'canonical',
            $array['checks']
        );

        self::assertArrayHasKey(
            'robots',
            $array['checks']
        );
    }
}
