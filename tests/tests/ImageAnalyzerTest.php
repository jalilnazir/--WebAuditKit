<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\ImageAnalyzer;

final class ImageAnalyzerTest extends TestCase
{
    private ImageAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ImageAnalyzer();
    }

    public function testDetectsValidImage(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="seo-audit.jpg"
                        alt="Website SEO audit dashboard"
                        width="800"
                        height="600"
                        loading="lazy"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['total']);
        $this->assertSame(0, $result['missing_alt']);
        $this->assertSame(0, $result['empty_alt']);
        $this->assertSame(0, $result['missing_dimensions']);
        $this->assertSame(1, $result['lazy_loaded']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMissingAltAttribute(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="example.jpg"
                        width="800"
                        height="600"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['missing_alt']);
        $this->assertNull($result['images'][0]['alt']);
        $this->assertFalse($result['images'][0]['has_alt']);
        $this->assertSame('error', $result['status']);
    }

    public function testDistinguishesEmptyAltFromMissingAlt(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="decorative.jpg"
                        alt=""
                        width="100"
                        height="100"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(0, $result['missing_alt']);
        $this->assertSame(1, $result['empty_alt']);

        $this->assertTrue(
            $result['images'][0]['has_alt']
        );

        $this->assertTrue(
            $result['images'][0]['empty_alt']
        );

        $this->assertSame('', $result['images'][0]['alt']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMissingDimensions(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="example.jpg"
                        alt="Example image"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['missing_dimensions']);

        $this->assertFalse(
            $result['images'][0]['has_dimensions']
        );

        $this->assertSame('warning', $result['status']);
    }

    public function testRequiresBothWidthAndHeight(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="example.jpg"
                        alt="Example image"
                        width="800"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['missing_dimensions']);

        $this->assertFalse(
            $result['images'][0]['has_dimensions']
        );

        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsLazyLoadedImage(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="example.jpg"
                        alt="Example image"
                        width="800"
                        height="600"
                        loading="lazy"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['lazy_loaded']);

        $this->assertTrue(
            $result['images'][0]['lazy_loaded']
        );

        $this->assertSame('pass', $result['status']);
    }

    public function testLoadingAttributeIsCaseInsensitive(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="example.jpg"
                        alt="Example image"
                        width="800"
                        height="600"
                        loading="LAZY"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['lazy_loaded']);

        $this->assertTrue(
            $result['images'][0]['lazy_loaded']
        );
    }

    public function testCountsMultipleImages(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="one.jpg"
                        alt="First image"
                        width="800"
                        height="600"
                    >

                    <img
                        src="two.jpg"
                        alt="Second image"
                        width="400"
                        height="300"
                        loading="lazy"
                    >

                    <img
                        src="three.jpg"
                        width="200"
                        height="200"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['missing_alt']);
        $this->assertSame(1, $result['lazy_loaded']);
        $this->assertSame('error', $result['status']);
    }

    public function testHandlesUnicodeAltText(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="arabic.jpg"
                        alt="تحليل تحسين محركات البحث"
                        width="800"
                        height="600"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            'تحليل تحسين محركات البحث',
            $result['images'][0]['alt']
        );

        $this->assertTrue(
            $result['images'][0]['has_alt']
        );

        $this->assertSame('pass', $result['status']);
    }

    public function testTrimsAltText(): void
    {
        $html = '
            <html>
                <body>
                    <img
                        src="example.jpg"
                        alt="   Website SEO Audit   "
                        width="800"
                        height="600"
                    >
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(
            'Website SEO Audit',
            $result['images'][0]['alt']
        );
    }

    public function testPageWithoutImagesPasses(): void
    {
        $html = '
            <html>
                <body>
                    <h1>Website SEO Audit</h1>
                    <p>This page contains no images.</p>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame([], $result['images']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['missing_alt']);
        $this->assertSame(0, $result['empty_alt']);
        $this->assertSame(0, $result['missing_dimensions']);
        $this->assertSame(0, $result['lazy_loaded']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertSame([], $result['images']);
        $this->assertSame(0, $result['total']);
        $this->assertSame('error', $result['status']);
    }

    public function testMissingAltHasPriorityOverMissingDimensions(): void
    {
        $html = '
            <html>
                <body>
                    <img src="example.jpg">
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['missing_alt']);
        $this->assertSame(1, $result['missing_dimensions']);

        /*
         * Missing alt is considered the more serious issue,
         * therefore the overall result should be an error.
         */
        $this->assertSame('error', $result['status']);
    }
}
