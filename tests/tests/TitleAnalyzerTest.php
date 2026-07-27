<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\TitleAnalyzer;

final class TitleAnalyzerTest extends TestCase
{
    private TitleAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new TitleAnalyzer();
    }

    public function testDetectsValidTitle(): void
    {
        $html = '<html><head><title>Complete Website SEO Audit Guide for Developers</title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(
            'Complete Website SEO Audit Guide for Developers',
            $result['title']
        );
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMissingTitle(): void
    {
        $html = '<html><head></head><body>Example</body></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['exists']);
        $this->assertNull($result['title']);
        $this->assertSame(0, $result['length']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsEmptyTitle(): void
    {
        $html = '<html><head><title>   </title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame('', $result['title']);
        $this->assertSame(0, $result['length']);
        $this->assertSame('error', $result['status']);
    }

    public function testWarnsAboutShortTitle(): void
    {
        $html = '<html><head><title>SEO Guide</title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame('SEO Guide', $result['title']);
        $this->assertSame(9, $result['length']);
        $this->assertSame('warning', $result['status']);
    }

    public function testWarnsAboutLongTitle(): void
    {
        $title = str_repeat('A', 61);

        $html = '<html><head><title>' . $title . '</title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(61, $result['length']);
        $this->assertSame('warning', $result['status']);
    }

    public function testNormalizesWhitespace(): void
    {
        $html = '<html><head><title>
            Complete     Website SEO
            Audit Guide for Developers
        </title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            'Complete Website SEO Audit Guide for Developers',
            $result['title']
        );

        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesUnicodeTitleLength(): void
    {
        $title = str_repeat('م', 35);

        $html = '<html><head><title>' . $title . '</title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(35, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertFalse($result['exists']);
        $this->assertNull($result['title']);
        $this->assertSame(0, $result['length']);
        $this->assertSame('error', $result['status']);
    }

    public function testMinimumRecommendedLengthPasses(): void
    {
        $title = str_repeat('A', 30);

        $result = $this->analyzer->analyze(
            '<html><head><title>' . $title . '</title></head></html>'
        );

        $this->assertSame(30, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testMaximumRecommendedLengthPasses(): void
    {
        $title = str_repeat('A', 60);

        $result = $this->analyzer->analyze(
            '<html><head><title>' . $title . '</title></head></html>'
        );

        $this->assertSame(60, $result['length']);
        $this->assertSame('pass', $result['status']);
    }
}
