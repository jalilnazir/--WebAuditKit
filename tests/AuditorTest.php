<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebAuditKit\Auditor;
use WebAuditKit\Result\AuditResult;

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

    <meta
        name="robots"
        content="index, follow"
    >

    <link
        rel="canonical"
        href="https://example.com/test"
    >
</head>

<body>
    <h1>Example Website</h1>
    <h2>About This Page</h2>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit(
            $html,
            'https://example.com/test'
        );

        self::assertInstanceOf(
            AuditResult::class,
            $result
        );

        self::assertSame(
            'https://example.com/test',
            $result->url()
        );

        self::assertSame(
            5,
            $result->totalCount()
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

        $title = $result->check('title');

        self::assertNotNull($title);
        self::assertTrue($title['exists']);

        self::assertSame(
            'Example Website SEO Audit Test Page',
            $title['title']
        );

        self::assertSame(
            'pass',
            $title['status']
        );

        $description = $result->check(
            'meta_description'
        );

        self::assertNotNull($description);
        self::assertTrue($description['exists']);

        self::assertSame(
            'pass',
            $description['status']
        );

        $headings = $result->check('headings');

        self::assertNotNull($headings);

        self::assertSame(
            1,
            $headings['h1_count']
        );

        self::assertTrue(
            $headings['has_h1']
        );

        self::assertFalse(
            $headings['multiple_h1']
        );

        self::assertSame(
            2,
            $headings['total']
        );

        self::assertSame(
            'Example Website',
            $headings['headings'][0]['text']
        );

        self::assertSame(
            1,
            $headings['headings'][0]['level']
        );

        self::assertSame(
            'About This Page',
            $headings['headings'][1]['text']
        );

        self::assertSame(
            2,
            $headings['headings'][1]['level']
        );

        $canonical = $result->check('canonical');

        self::assertNotNull($canonical);
        self::assertTrue($canonical['exists']);

        self::assertSame(
            'https://example.com/test',
            $canonical['canonical']
        );

        self::assertTrue(
            $canonical['self_referencing']
        );

        self::assertSame(
            'pass',
            $canonical['status']
        );

        $robots = $result->check('robots');

        self::assertNotNull($robots);
        self::assertTrue($robots['exists']);
        self::assertTrue($robots['indexable']);
        self::assertTrue($robots['followable']);
        self::assertFalse($robots['conflicting']);

        self::assertSame(
            ['index', 'follow'],
            $robots['directives']
        );

        self::assertSame(
            'pass',
            $robots['status']
        );

        self::assertSame(
            'pass',
            $result->status()
        );

        self::assertTrue(
            $result->hasPassed()
        );
    }

    public function testMissingSeoElementsAreReported(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
</head>

<body>
    <p>Page without important SEO elements.</p>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit(
            $html,
            'https://example.com'
        );

        self::assertInstanceOf(
            AuditResult::class,
            $result
        );

        $title = $result->check('title');

        self::assertNotNull($title);
        self::assertFalse($title['exists']);
        self::assertNull($title['title']);
        self::assertSame('error', $title['status']);

        $description = $result->check(
            'meta_description'
        );

        self::assertNotNull($description);
        self::assertFalse($description['exists']);
        self::assertNull($description['description']);

        self::assertSame(
            'error',
            $description['status']
        );

        $headings = $result->check('headings');

        self::assertNotNull($headings);

        self::assertSame(
            0,
            $headings['h1_count']
        );

        self::assertFalse(
            $headings['has_h1']
        );

        self::assertSame(
            'warning',
            $headings['status']
        );

        $canonical = $result->check('canonical');

        self::assertNotNull($canonical);
        self::assertFalse($canonical['exists']);
        self::assertNull($canonical['canonical']);

        self::assertSame(
            'warning',
            $canonical['status']
        );

        /*
         * No robots meta tag means the normal crawler
         * default is index, follow.
         */
        $robots = $result->check('robots');

        self::assertNotNull($robots);
        self::assertFalse($robots['exists']);
        self::assertTrue($robots['indexable']);
        self::assertTrue($robots['followable']);

        self::assertSame(
            'pass',
            $robots['status']
        );

        self::assertSame(
            'error',
            $result->status()
        );

        self::assertTrue(
            $result->hasErrors()
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

        $robots = $result->check('robots');

        self::assertNotNull($robots);

        self::assertContains(
            'noindex',
            $robots['directives']
        );

        self::assertContains(
            'nofollow',
            $robots['directives']
        );

        self::assertFalse(
            $robots['indexable']
        );

        self::assertFalse(
            $robots['followable']
        );

        self::assertFalse(
            $robots['conflicting']
        );

        self::assertSame(
            'warning',
            $robots['status']
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

        $headings = $result->check('headings');

        self::assertNotNull($headings);

        self::assertSame(
            2,
            $headings['h1_count']
        );

        self::assertTrue(
            $headings['has_h1']
        );

        self::assertTrue(
            $headings['multiple_h1']
        );

        self::assertSame(
            2,
            $headings['counts']['h1']
        );

        self::assertSame(
            'warning',
            $headings['status']
        );
    }

    public function testAuditWithoutUrlStoresNullUrl(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Complete Website Audit Test</title>
</head>
<body>
    <h1>Website Audit</h1>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit($html);

        self::assertNull(
            $result->url()
        );
    }

    public function testAuditResultCanBeConvertedToArray(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Complete Website Audit Test</title>

    <meta
        name="description"
        content="This description contains enough useful content for testing the structured WebAuditKit audit result model."
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
    <h1>Complete Website Audit Test</h1>
</body>
</html>
HTML;

        $auditor = new Auditor();

        $result = $auditor->audit(
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

        self::assertCount(
            5,
            $array['checks']
        );
    }

    public function testEmptyHtmlThrowsException(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'HTML content cannot be empty.'
        );

        $auditor = new Auditor();

        $auditor->audit('');
    }
}
