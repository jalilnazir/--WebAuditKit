<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\CanonicalAnalyzer;

final class CanonicalAnalyzerTest extends TestCase
{
    private CanonicalAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new CanonicalAnalyzer();
    }

    public function testDetectsValidCanonical(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(1, $result['count']);
        $this->assertFalse($result['multiple']);
        $this->assertFalse($result['empty']);
        $this->assertTrue($result['absolute']);
        $this->assertTrue($result['valid_url']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMissingCanonical(): void
    {
        $html = '
            <html>
                <head>
                    <title>Example</title>
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['exists']);
        $this->assertNull($result['canonical']);
        $this->assertSame(0, $result['count']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsEmptyCanonicalHref(): void
    {
        $html = '
            <html>
                <head>
                    <link rel="canonical" href="">
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame('', $result['canonical']);
        $this->assertTrue($result['empty']);
        $this->assertFalse($result['absolute']);
        $this->assertFalse($result['valid_url']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsRelativeCanonical(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="/products/example"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertFalse($result['absolute']);
        $this->assertFalse($result['valid_url']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsMultipleCanonicals(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/one"
                    >

                    <link
                        rel="canonical"
                        href="https://example.com/two"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(2, $result['count']);
        $this->assertTrue($result['multiple']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsSelfReferencingCanonical(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com/page'
        );

        $this->assertTrue($result['self_referencing']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsNonSelfReferencingCanonical(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/preferred-page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com/current-page'
        );

        $this->assertFalse($result['self_referencing']);

        /*
         * Cross-page canonicals are legitimate and therefore
         * are not automatically considered an error.
         */
        $this->assertSame('pass', $result['status']);
    }

    public function testSelfReferencingIsNullWithoutPageUrl(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertNull($result['self_referencing']);
    }

    public function testNormalizesTrailingSlashForComparison(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/page/"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com/page'
        );

        $this->assertTrue($result['self_referencing']);
    }

    public function testHostComparisonIsCaseInsensitive(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://EXAMPLE.COM/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com/page'
        );

        $this->assertTrue($result['self_referencing']);
    }

    public function testDetectsCanonicalRelCaseInsensitively(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="CANONICAL"
                        href="https://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsCanonicalAmongMultipleRelTokens(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="alternate canonical"
                        href="https://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDoesNotMistakeSimilarRelForCanonical(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical-other"
                        href="https://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['exists']);
        $this->assertSame(0, $result['count']);
        $this->assertSame('warning', $result['status']);
    }

    public function testRejectsNonHttpCanonicalScheme(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="ftp://example.com/page"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['absolute']);
        $this->assertFalse($result['valid_url']);
        $this->assertSame('warning', $result['status']);
    }

    public function testHandlesUnicodeCanonicalUrl(): void
    {
        $html = '
            <html>
                <head>
                    <link
                        rel="canonical"
                        href="https://example.com/arabic"
                    >
                </head>
                <body>
                    <h1>تحسين محركات البحث</h1>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            'https://example.com/arabic',
            $result['canonical']
        );

        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertFalse($result['exists']);
        $this->assertNull($result['canonical']);
        $this->assertSame(0, $result['count']);
        $this->assertSame('error', $result['status']);
    }
}
