<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\HeadingAnalyzer;

final class HeadingAnalyzerTest extends TestCase
{
    private HeadingAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new HeadingAnalyzer();
    }

    public function testDetectsValidHeadingStructure(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Website SEO Audit</h1>
                    <h2>Technical SEO</h2>
                    <h3>Crawlability</h3>
                    <h2>On-Page SEO</h2>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['has_h1']);
        $this->assertFalse($result['multiple_h1']);
        $this->assertTrue($result['hierarchy_valid']);
        $this->assertSame(4, $result['total']);
        $this->assertSame(1, $result['h1_count']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMissingH1(): void
    {
        $html = '
            <html>
                <body>
                    <h2>Technical SEO</h2>
                    <h3>Crawlability</h3>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['has_h1']);
        $this->assertSame(0, $result['h1_count']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsMultipleH1Headings(): void
    {
        $html = '
            <html>
                <body>
                    <h1>First Heading</h1>
                    <h1>Second Heading</h1>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['has_h1']);
        $this->assertTrue($result['multiple_h1']);
        $this->assertSame(2, $result['h1_count']);
        $this->assertSame('warning', $result['status']);
    }

    public function testCountsAllHeadingLevels(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Heading 1</h1>
                    <h2>Heading 2</h2>
                    <h2>Another Heading 2</h2>
                    <h3>Heading 3</h3>
                    <h4>Heading 4</h4>
                    <h5>Heading 5</h5>
                    <h6>Heading 6</h6>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['counts']['h1']);
        $this->assertSame(2, $result['counts']['h2']);
        $this->assertSame(1, $result['counts']['h3']);
        $this->assertSame(1, $result['counts']['h4']);
        $this->assertSame(1, $result['counts']['h5']);
        $this->assertSame(1, $result['counts']['h6']);
        $this->assertSame(7, $result['total']);
    }

    public function testDetectsSkippedHeadingLevel(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Main Heading</h1>
                    <h3>Skipped H2</h3>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['hierarchy_valid']);
        $this->assertSame('warning', $result['status']);
    }

    public function testAllowsHeadingLevelToMoveUp(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Main Heading</h1>
                    <h2>Section</h2>
                    <h3>Subsection</h3>
                    <h2>Another Section</h2>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['hierarchy_valid']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsEmptyHeading(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Main Heading</h1>
                    <h2>   </h2>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['empty_count']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsNoHeadings(): void
    {
        $html = '
            <html>
                <body>
                    <p>This page contains no headings.</p>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(0, $result['total']);
        $this->assertFalse($result['has_h1']);
        $this->assertSame('error', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertSame([], $result['headings']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['h1_count']);
        $this->assertFalse($result['has_h1']);
        $this->assertSame('error', $result['status']);
    }

    public function testNormalizesHeadingWhitespace(): void
    {
        $html = '
            <html>
                <body>
                    <h1>
                        Complete     Website
                        SEO Audit
                    </h1>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            'Complete Website SEO Audit',
            $result['headings'][0]['text']
        );

        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesUnicodeHeadings(): void
    {
        $html = '
            <html>
                <body>
                    <h1>دليل تحسين محركات البحث</h1>
                    <h2>تحسين الموقع</h2>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            'دليل تحسين محركات البحث',
            $result['headings'][0]['text']
        );

        $this->assertSame(
            'تحسين الموقع',
            $result['headings'][1]['text']
        );

        $this->assertSame(2, $result['total']);
        $this->assertTrue($result['hierarchy_valid']);
        $this->assertSame('pass', $result['status']);
    }

    public function testReturnsHeadingLevelsAndText(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Main Topic</h1>
                    <h2>First Section</h2>
                    <h3>Subsection</h3>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            [
                ['level' => 1, 'text' => 'Main Topic'],
                ['level' => 2, 'text' => 'First Section'],
                ['level' => 3, 'text' => 'Subsection'],
            ],
            $result['headings']
        );
    }
}
