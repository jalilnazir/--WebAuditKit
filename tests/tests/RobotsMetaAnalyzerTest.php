<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\RobotsMetaAnalyzer;

final class RobotsMetaAnalyzerTest extends TestCase
{
    private RobotsMetaAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new RobotsMetaAnalyzer();
    }

    public function testDefaultsToIndexFollowWithoutRobotsMeta(): void
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
        $this->assertSame(0, $result['count']);
        $this->assertNull($result['content']);
        $this->assertSame([], $result['directives']);
        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertFalse($result['conflicting']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsIndexFollow(): void
    {
        $html = '
            <html>
                <head>
                    <meta
                        name="robots"
                        content="index, follow"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(1, $result['count']);
        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertContains('index', $result['directives']);
        $this->assertContains('follow', $result['directives']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsNoindex(): void
    {
        $html = '
            <meta
                name="robots"
                content="noindex, follow"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertContains('noindex', $result['directives']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsNofollow(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, nofollow"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['indexable']);
        $this->assertFalse($result['followable']);
        $this->assertContains('nofollow', $result['directives']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsNoindexAndNofollow(): void
    {
        $html = '
            <meta
                name="robots"
                content="noindex, nofollow"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertFalse($result['indexable']);
        $this->assertFalse($result['followable']);
        $this->assertSame('warning', $result['status']);
    }

    public function testNoneMeansNoindexNofollow(): void
    {
        $html = '
            <meta
                name="robots"
                content="none"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertContains('none', $result['directives']);
        $this->assertFalse($result['indexable']);
        $this->assertFalse($result['followable']);
        $this->assertFalse($result['conflicting']);
        $this->assertSame('warning', $result['status']);
    }

    public function testAllAllowsIndexAndFollow(): void
    {
        $html = '
            <meta
                name="robots"
                content="all"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertContains('all', $result['directives']);
        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertFalse($result['conflicting']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsIndexNoindexConflict(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, noindex"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['conflicting']);
        $this->assertFalse($result['indexable']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsFollowNofollowConflict(): void
    {
        $html = '
            <meta
                name="robots"
                content="follow, nofollow"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['conflicting']);
        $this->assertFalse($result['followable']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsAllNoindexConflict(): void
    {
        $html = '
            <meta
                name="robots"
                content="all, noindex"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['conflicting']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsNoneIndexConflict(): void
    {
        $html = '
            <meta
                name="robots"
                content="none, index"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['conflicting']);
        $this->assertFalse($result['indexable']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsNoarchive(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, follow, noarchive"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['noarchive']);
        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsNosnippet(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, follow, nosnippet"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['nosnippet']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsNoimageindex(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, follow, noimageindex"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['noimageindex']);
        $this->assertSame('pass', $result['status']);
    }

    public function testRobotsNameIsCaseInsensitive(): void
    {
        $html = '
            <meta
                name="ROBOTS"
                content="INDEX, FOLLOW"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertContains('index', $result['directives']);
        $this->assertContains('follow', $result['directives']);
        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertSame('pass', $result['status']);
    }

    public function testParsesWhitespaceSeparatedDirectives(): void
    {
        $html = '
            <meta
                name="robots"
                content="index follow noarchive"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertContains('index', $result['directives']);
        $this->assertContains('follow', $result['directives']);
        $this->assertContains('noarchive', $result['directives']);
        $this->assertTrue($result['noarchive']);
    }

    public function testRemovesDuplicateDirectives(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, follow, index, follow"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            ['index', 'follow'],
            $result['directives']
        );
    }

    public function testCombinesMultipleRobotsMetaTags(): void
    {
        $html = '
            <html>
                <head>
                    <meta
                        name="robots"
                        content="index"
                    >

                    <meta
                        name="robots"
                        content="follow, noarchive"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(2, $result['count']);
        $this->assertContains('index', $result['directives']);
        $this->assertContains('follow', $result['directives']);
        $this->assertContains('noarchive', $result['directives']);
        $this->assertTrue($result['noarchive']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsConflictAcrossMultipleRobotsTags(): void
    {
        $html = '
            <html>
                <head>
                    <meta
                        name="robots"
                        content="index"
                    >

                    <meta
                        name="robots"
                        content="noindex"
                    >
                </head>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(2, $result['count']);
        $this->assertTrue($result['conflicting']);
        $this->assertFalse($result['indexable']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsEmptyRobotsContent(): void
    {
        $html = '
            <meta
                name="robots"
                content=""
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['exists']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('', $result['content']);
        $this->assertSame([], $result['directives']);
        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertSame('warning', $result['status']);
    }

    public function testUnknownDirectiveDoesNotBreakAnalysis(): void
    {
        $html = '
            <meta
                name="robots"
                content="index, follow, custom-directive"
            >
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertContains(
            'custom-directive',
            $result['directives']
        );

        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesUnicodeDocument(): void
    {
        $html = '
            <html>
                <head>
                    <meta
                        name="robots"
                        content="index, follow"
                    >
                </head>

                <body>
                    <h1>دليل تحسين محركات البحث</h1>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertTrue($result['indexable']);
        $this->assertTrue($result['followable']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertFalse($result['exists']);
        $this->assertSame(0, $result['count']);
        $this->assertNull($result['content']);
        $this->assertSame([], $result['directives']);
        $this->assertSame('error', $result['status']);
    }
}
