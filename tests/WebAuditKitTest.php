<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
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

        self::assertIsArray($result);
        self::assertNotEmpty($result);
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

        self::assertIsArray($result);
    }
}
