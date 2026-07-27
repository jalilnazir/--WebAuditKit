<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebAuditKit\Auditor;

final class AuditorTest extends TestCase
{
    public function testCompletePageCanBeAudited(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Example Website SEO Audit Test Page</title>

    <meta
        name="description"
        content="This is an example meta description used to test the WebAuditKit website auditing engine and its SEO analysis functionality."
    >

    <meta name="robots" content="index, follow">

    <link
        rel="canonical"
        href="https://example.com/test"
    >
</head>

<body>
    <h1>Example Website</h1>
    <h2>About This Page</h2>

    <img
        src="/image.jpg"
        alt="Example image"
    >

    <a href="/about">About</a>
    <a href="https://example.com/contact">Contact</a>
    <a href="https://external.example">External</a>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit(
            $html,
            'https://example.com/test'
        );

        self::assertSame(
            'https://example.com/test',
            $result['url']
        );

        self::assertTrue(
            $result['title']['exists']
        );

        self::assertSame(
            'Example Website SEO Audit Test Page',
            $result['title']['value']
        );

        self::assertTrue(
            $result['meta_description']['exists']
        );

        self::assertSame(
            ['Example Website'],
            $result['headings']['h1']
        );

        self::assertSame(
            ['About This Page'],
            $result['headings']['h2']
        );

        self::assertTrue(
            $result['canonical']['exists']
        );

        self::assertSame(
            'https://example.com/test',
            $result['canonical']['value']
        );

        self::assertFalse(
            $result['robots']['noindex']
        );

        self::assertFalse(
            $result['robots']['nofollow']
        );

        self::assertSame(
            1,
            $result['images']['total']
        );

        self::assertSame(
            0,
            $result['images']['missing_alt']
        );

        self::assertSame(
            0,
            $result['images']['empty_alt']
        );

        self::assertSame(
            3,
            $result['links']['total']
        );

        self::assertSame(
            2,
            $result['links']['internal']
        );

        self::assertSame(
            1,
            $result['links']['external']
        );
    }

    public function testMissingSeoElementsGenerateWarnings(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
</head>

<body>
    <p>Page without important SEO elements.</p>

    <img src="/missing-alt.jpg">
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit(
            $html,
            'https://example.com'
        );

        self::assertFalse(
            $result['title']['exists']
        );

        self::assertFalse(
            $result['meta_description']['exists']
        );

        self::assertFalse(
            $result['canonical']['exists']
        );

        self::assertSame(
            0,
            count($result['headings']['h1'])
        );

        self::assertSame(
            1,
            $result['images']['missing_alt']
        );

        self::assertContains(
            'Page title is missing.',
            $result['summary']['warnings']
        );

        self::assertContains(
            'Meta description is missing.',
            $result['summary']['warnings']
        );

        self::assertContains(
            'No H1 heading found.',
            $result['summary']['warnings']
        );

        self::assertContains(
            'Canonical URL is missing.',
            $result['summary']['warnings']
        );
    }

    public function testNoindexAndNofollowAreDetected(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Robots Directive Test Page</title>

    <meta
        name="robots"
        content="noindex, nofollow"
    >
</head>

<body>
    <h1>Robots Test</h1>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit($html);

        self::assertTrue(
            $result['robots']['noindex']
        );

        self::assertTrue(
            $result['robots']['nofollow']
        );
    }

    public function testMultipleH1HeadingsAreReported(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Multiple Heading Test Page</title>
</head>

<body>
    <h1>First Heading</h1>
    <h1>Second Heading</h1>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit($html);

        self::assertCount(
            2,
            $result['headings']['h1']
        );

        self::assertContains(
            'Multiple H1 headings were found.',
            $result['summary']['warnings']
        );
    }

    public function testEmptyHtmlThrowsException(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $auditor = new Auditor();

        $auditor->audit('');
    }
}
