<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\MetaDescriptionAnalyzer;

final class MetaDescriptionAnalyzerTest extends TestCase
{
    private MetaDescriptionAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new MetaDescriptionAnalyzer();
    }

    public function testDetectsValidMetaDescription(): void
    {
        $description = str_repeat('A', 140);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame($description, $result['description']);
        $this->assertSame(140, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMissingMetaDescription(): void
    {
        $html = '<html><head><title>Example</title></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['exists']);
        $this->assertNull($result['description']);
        $this->assertSame(0, $result['length']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsEmptyMetaDescription(): void
    {
        $html = '<html><head><meta name="description" content=""></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame('', $result['description']);
        $this->assertSame(0, $result['length']);
        $this->assertSame('error', $result['status']);
    }

    public function testWarnsAboutShortMetaDescription(): void
    {
        $description = str_repeat('A', 119);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(119, $result['length']);
        $this->assertSame('warning', $result['status']);
    }

    public function testWarnsAboutLongMetaDescription(): void
    {
        $description = str_repeat('A', 161);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(161, $result['length']);
        $this->assertSame('warning', $result['status']);
    }

    public function testMinimumRecommendedLengthPasses(): void
    {
        $description = str_repeat('A', 120);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(120, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testMaximumRecommendedLengthPasses(): void
    {
        $description = str_repeat('A', 160);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(160, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesUnicodeMetaDescriptionLength(): void
    {
        $description = str_repeat('م', 140);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(140, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testMetaDescriptionNameIsCaseInsensitive(): void
    {
        $description = str_repeat('A', 140);

        $html = '<html><head><meta name="DESCRIPTION" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(140, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testNormalizesWhitespace(): void
    {
        $description =
            str_repeat('A', 60) .
            '     ' .
            str_repeat('B', 60);

        $html = '<html><head><meta name="description" content="' .
            $description .
            '"></head></html>';

        $result = $this->analyzer->analyze($html);

        $expected =
            str_repeat('A', 60) .
            ' ' .
            str_repeat('B', 60);

        $this->assertSame($expected, $result['description']);
        $this->assertSame(121, $result['length']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertFalse($result['exists']);
        $this->assertNull($result['description']);
        $this->assertSame(0, $result['length']);
        $this->assertSame('error', $result['status']);
    }
}
