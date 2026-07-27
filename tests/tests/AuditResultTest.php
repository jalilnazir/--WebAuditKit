<?php

declare(strict_types=1);

namespace WebAuditKit\Tests;

use PHPUnit\Framework\TestCase;
use WebAuditKit\Result\AuditResult;

final class AuditResultTest extends TestCase
{
    public function testCreatesEmptyAuditResult(): void
    {
        $result = new AuditResult();

        $this->assertNull($result->url());
        $this->assertSame([], $result->checks());
        $this->assertSame(0, $result->totalCount());
        $this->assertSame(0, $result->passedCount());
        $this->assertSame(0, $result->warningCount());
        $this->assertSame(0, $result->errorCount());
        $this->assertSame('pass', $result->status());
    }

    public function testStoresAuditUrl(): void
    {
        $result = new AuditResult(
            'https://example.com'
        );

        $this->assertSame(
            'https://example.com',
            $result->url()
        );
    }

    public function testAcceptsInitialChecks(): void
    {
        $checks = [
            'title' => [
                'status' => 'pass',
                'message' => 'The title passed.',
            ],
            'canonical' => [
                'status' => 'warning',
                'message' => 'Canonical warning.',
            ],
        ];

        $result = new AuditResult(
            'https://example.com',
            $checks
        );

        $this->assertSame($checks, $result->checks());
        $this->assertSame(2, $result->totalCount());
    }

    public function testAddsCheck(): void
    {
        $result = new AuditResult();

        $returned = $result->addCheck(
            'title',
            [
                'status' => 'pass',
                'message' => 'The title passed.',
            ]
        );

        $this->assertSame($result, $returned);
        $this->assertTrue($result->hasCheck('title'));

        $this->assertSame(
            [
                'status' => 'pass',
                'message' => 'The title passed.',
            ],
            $result->check('title')
        );
    }

    public function testAddCheckReplacesExistingCheck(): void
    {
        $result = new AuditResult();

        $result->addCheck(
            'title',
            [
                'status' => 'warning',
            ]
        );

        $result->addCheck(
            'title',
            [
                'status' => 'pass',
            ]
        );

        $this->assertSame(
            [
                'status' => 'pass',
            ],
            $result->check('title')
        );

        $this->assertSame(1, $result->totalCount());
    }

    public function testHasCheckReturnsFalseForMissingCheck(): void
    {
        $result = new AuditResult();

        $this->assertFalse(
            $result->hasCheck('title')
        );
    }

    public function testCheckReturnsNullForMissingCheck(): void
    {
        $result = new AuditResult();

        $this->assertNull(
            $result->check('canonical')
        );
    }

    public function testReturnsPassedChecks(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'warning',
                ],
                'canonical' => [
                    'status' => 'pass',
                ],
            ]
        );

        $passed = $result->passed();

        $this->assertCount(2, $passed);
        $this->assertArrayHasKey('title', $passed);
        $this->assertArrayHasKey('canonical', $passed);
        $this->assertArrayNotHasKey(
            'description',
            $passed
        );
    }

    public function testReturnsWarningChecks(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'warning',
                ],
                'robots' => [
                    'status' => 'warning',
                ],
            ]
        );

        $warnings = $result->warnings();

        $this->assertCount(2, $warnings);
        $this->assertArrayHasKey(
            'description',
            $warnings
        );
        $this->assertArrayHasKey(
            'robots',
            $warnings
        );
    }

    public function testReturnsErrorChecks(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'error',
                ],
                'description' => [
                    'status' => 'pass',
                ],
                'canonical' => [
                    'status' => 'error',
                ],
            ]
        );

        $errors = $result->errors();

        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey(
            'canonical',
            $errors
        );
    }

    public function testCountsStatuses(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'pass',
                ],
                'headings' => [
                    'status' => 'warning',
                ],
                'canonical' => [
                    'status' => 'error',
                ],
            ]
        );

        $this->assertSame(
            4,
            $result->totalCount()
        );

        $this->assertSame(
            2,
            $result->passedCount()
        );

        $this->assertSame(
            1,
            $result->warningCount()
        );

        $this->assertSame(
            1,
            $result->errorCount()
        );
    }

    public function testOverallStatusIsPassWhenAllChecksPass(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'pass',
                ],
            ]
        );

        $this->assertSame(
            'pass',
            $result->status()
        );

        $this->assertTrue(
            $result->hasPassed()
        );

        $this->assertFalse(
            $result->hasWarnings()
        );

        $this->assertFalse(
            $result->hasErrors()
        );
    }

    public function testOverallStatusIsWarningWhenWarningExists(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'warning',
                ],
            ]
        );

        $this->assertSame(
            'warning',
            $result->status()
        );

        $this->assertFalse(
            $result->hasPassed()
        );

        $this->assertTrue(
            $result->hasWarnings()
        );

        $this->assertFalse(
            $result->hasErrors()
        );
    }

    public function testErrorHasPriorityOverWarning(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'warning',
                ],
                'canonical' => [
                    'status' => 'error',
                ],
            ]
        );

        $this->assertSame(
            'error',
            $result->status()
        );

        $this->assertTrue(
            $result->hasWarnings()
        );

        $this->assertTrue(
            $result->hasErrors()
        );

        $this->assertFalse(
            $result->hasPassed()
        );
    }

    public function testSummaryContainsCorrectCounts(): void
    {
        $result = new AuditResult(
            'https://example.com',
            [
                'title' => [
                    'status' => 'pass',
                ],
                'description' => [
                    'status' => 'pass',
                ],
                'headings' => [
                    'status' => 'warning',
                ],
                'canonical' => [
                    'status' => 'error',
                ],
            ]
        );

        $this->assertSame(
            [
                'total' => 4,
                'passed' => 2,
                'warnings' => 1,
                'errors' => 1,
                'status' => 'error',
            ],
            $result->summary()
        );
    }

    public function testToArrayReturnsStructuredResult(): void
    {
        $checks = [
            'title' => [
                'status' => 'pass',
                'message' => 'The title passed.',
            ],
            'robots' => [
                'status' => 'warning',
                'message' => 'The page is noindex.',
            ],
        ];

        $result = new AuditResult(
            'https://example.com',
            $checks
        );

        $this->assertSame(
            [
                'url' => 'https://example.com',
                'status' => 'warning',
                'summary' => [
                    'total' => 2,
                    'passed' => 1,
                    'warnings' => 1,
                    'errors' => 0,
                    'status' => 'warning',
                ],
                'checks' => $checks,
            ],
            $result->toArray()
        );
    }

    public function testCheckWithoutStatusIsExcludedFromSeverityCounts(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'message' => 'No status supplied.',
                ],
                'canonical' => [
                    'status' => 'pass',
                ],
            ]
        );

        $this->assertSame(
            2,
            $result->totalCount()
        );

        $this->assertSame(
            1,
            $result->passedCount()
        );

        $this->assertSame(
            0,
            $result->warningCount()
        );

        $this->assertSame(
            0,
            $result->errorCount()
        );

        $this->assertSame(
            'pass',
            $result->status()
        );
    }

    public function testUnknownStatusIsExcludedFromSeverityCounts(): void
    {
        $result = new AuditResult(
            null,
            [
                'title' => [
                    'status' => 'unknown',
                ],
                'canonical' => [
                    'status' => 'pass',
                ],
            ]
        );

        $this->assertSame(
            2,
            $result->totalCount()
        );

        $this->assertSame(
            1,
            $result->passedCount()
        );

        $this->assertSame(
            0,
            $result->warningCount()
        );

        $this->assertSame(
            0,
            $result->errorCount()
        );
    }

    public function testSupportsRealAnalyzerStyleResults(): void
    {
        $result = new AuditResult(
            'https://example.com'
        );

        $result
            ->addCheck(
                'title',
                [
                    'title' => 'Complete Website SEO Audit Guide',
                    'exists' => true,
                    'length' => 32,
                    'status' => 'pass',
                    'message' => 'The page title is valid.',
                ]
            )
            ->addCheck(
                'canonical',
                [
                    'canonical' => 'https://example.com',
                    'exists' => true,
                    'count' => 1,
                    'status' => 'pass',
                    'message' => 'The canonical URL is valid.',
                ]
            )
            ->addCheck(
                'robots',
                [
                    'exists' => true,
                    'directives' => [
                        'index',
                        'follow',
                    ],
                    'indexable' => true,
                    'followable' => true,
                    'status' => 'pass',
                    'message' => 'Indexing is allowed.',
                ]
            );

        $this->assertSame(
            3,
            $result->totalCount()
        );

        $this->assertSame(
            3,
            $result->passedCount()
        );

        $this->assertSame(
            'pass',
            $result->status()
        );

        $this->assertTrue(
            $result->hasPassed()
        );
    }
}
