<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Analysis\LinkAnalyzer;

final class LinkAnalyzerTest extends TestCase
{
    private LinkAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new LinkAnalyzer();
    }

    public function testDetectsRelativeInternalLink(): void
    {
        $html = '<a href="/about">About Us</a>';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['internal']);
        $this->assertSame(0, $result['external']);
        $this->assertSame('internal', $result['links'][0]['type']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsAbsoluteInternalLink(): void
    {
        $html = '<a href="https://example.com/contact">Contact</a>';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(1, $result['internal']);
        $this->assertSame(0, $result['external']);
        $this->assertSame('internal', $result['links'][0]['type']);
    }

    public function testDetectsExternalLink(): void
    {
        $html = '<a href="https://other-example.com">External</a>';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(0, $result['internal']);
        $this->assertSame(1, $result['external']);
        $this->assertSame('external', $result['links'][0]['type']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsProtocolRelativeInternalLink(): void
    {
        $html = '<a href="//example.com/about">About</a>';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(1, $result['internal']);
        $this->assertSame('internal', $result['links'][0]['type']);
    }

    public function testDetectsProtocolRelativeExternalLink(): void
    {
        $html = '<a href="//other-example.com">External</a>';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(1, $result['external']);
        $this->assertSame('external', $result['links'][0]['type']);
    }

    public function testDetectsAnchorLink(): void
    {
        $html = '<a href="#pricing">Pricing</a>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['anchors']);
        $this->assertSame('anchor', $result['links'][0]['type']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsMailtoLink(): void
    {
        $html = '<a href="mailto:hello@example.com">Email Us</a>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['mailto']);
        $this->assertSame('mailto', $result['links'][0]['type']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsTelephoneLink(): void
    {
        $html = '<a href="tel:+97312345678">Call Us</a>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['tel']);
        $this->assertSame('tel', $result['links'][0]['type']);
        $this->assertSame('pass', $result['status']);
    }

    public function testDetectsJavascriptLink(): void
    {
        $html = '<a href="javascript:void(0)">Click</a>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['javascript']);
        $this->assertSame('javascript', $result['links'][0]['type']);
        $this->assertSame('error', $result['status']);
    }

    public function testDetectsMissingHref(): void
    {
        $html = '<a>Missing href</a>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['missing_href']);
        $this->assertFalse($result['links'][0]['has_href']);
        $this->assertNull($result['links'][0]['href']);
        $this->assertSame('missing', $result['links'][0]['type']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsEmptyHref(): void
    {
        $html = '<a href="">Empty href</a>';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['empty_href']);
        $this->assertTrue($result['links'][0]['has_href']);
        $this->assertTrue($result['links'][0]['empty_href']);
        $this->assertSame('', $result['links'][0]['href']);
        $this->assertSame('empty', $result['links'][0]['type']);
        $this->assertSame('warning', $result['status']);
    }

    public function testDetectsNofollow(): void
    {
        $html = '
            <a
                href="https://other-example.com"
                rel="nofollow"
            >
                External
            </a>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['nofollow']);
        $this->assertTrue($result['links'][0]['nofollow']);
    }

    public function testDetectsSponsoredAndUgcRelValues(): void
    {
        $html = '
            <a
                href="https://other-example.com"
                rel="nofollow sponsored ugc"
            >
                External
            </a>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['nofollow']);
        $this->assertSame(1, $result['sponsored']);
        $this->assertSame(1, $result['ugc']);

        $this->assertTrue($result['links'][0]['nofollow']);
        $this->assertTrue($result['links'][0]['sponsored']);
        $this->assertTrue($result['links'][0]['ugc']);
    }

    public function testRelValuesAreCaseInsensitive(): void
    {
        $html = '
            <a
                href="https://other-example.com"
                rel="NOFOLLOW SPONSORED UGC"
            >
                External
            </a>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['nofollow']);
        $this->assertSame(1, $result['sponsored']);
        $this->assertSame(1, $result['ugc']);
    }

    public function testNormalizesLinkTextWhitespace(): void
    {
        $html = '
            <a href="/seo">
                Complete     Website
                SEO Audit
            </a>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(
            'Complete Website SEO Audit',
            $result['links'][0]['text']
        );
    }

    public function testHandlesUnicodeLinkText(): void
    {
        $html = '
            <a href="/seo">
                دليل تحسين محركات البحث
            </a>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(
            'دليل تحسين محركات البحث',
            $result['links'][0]['text']
        );

        $this->assertSame('internal', $result['links'][0]['type']);
    }

    public function testCountsMultipleLinkTypes(): void
    {
        $html = '
            <a href="/about">About</a>
            <a href="https://example.com/contact">Contact</a>
            <a href="https://other-example.com">External</a>
            <a href="#faq">FAQ</a>
            <a href="mailto:hello@example.com">Email</a>
            <a href="tel:+97312345678">Call</a>
        ';

        $result = $this->analyzer->analyze(
            $html,
            'https://example.com'
        );

        $this->assertSame(6, $result['total']);
        $this->assertSame(2, $result['internal']);
        $this->assertSame(1, $result['external']);
        $this->assertSame(1, $result['anchors']);
        $this->assertSame(1, $result['mailto']);
        $this->assertSame(1, $result['tel']);
        $this->assertSame('pass', $result['status']);
    }

    public function testPageWithoutLinksPasses(): void
    {
        $html = '
            <html>
                <body>
                    <h1>SEO Audit</h1>
                    <p>This page contains no links.</p>
                </body>
            </html>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame([], $result['links']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['internal']);
        $this->assertSame(0, $result['external']);
        $this->assertSame('pass', $result['status']);
    }

    public function testHandlesEmptyHtml(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertSame([], $result['links']);
        $this->assertSame(0, $result['total']);
        $this->assertSame('error', $result['status']);
    }

    public function testJavascriptErrorHasPriorityOverMissingHrefWarning(): void
    {
        $html = '
            <a href="javascript:void(0)">JavaScript</a>
            <a>Missing href</a>
        ';

        $result = $this->analyzer->analyze($html);

        $this->assertSame(1, $result['javascript']);
        $this->assertSame(1, $result['missing_href']);
        $this->assertSame('error', $result['status']);
    }
}
