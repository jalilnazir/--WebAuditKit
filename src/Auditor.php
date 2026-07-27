<?php

declare(strict_types=1);

namespace WebAuditKit;

use InvalidArgumentException;
use WebAuditKit\Analysis\CanonicalAnalyzer;
use WebAuditKit\Analysis\HeadingAnalyzer;
use WebAuditKit\Analysis\MetaDescriptionAnalyzer;
use WebAuditKit\Analysis\RobotsMetaAnalyzer;
use WebAuditKit\Analysis\TitleAnalyzer;
use WebAuditKit\Result\AuditResult;

/**
 * Core website auditing engine for WebAuditKit.
 *
 * Coordinates the individual analyzers and combines their
 * findings into a single AuditResult.
 */
final class Auditor
{
    private TitleAnalyzer $titleAnalyzer;

    private MetaDescriptionAnalyzer $metaDescriptionAnalyzer;

    private HeadingAnalyzer $headingAnalyzer;

    private CanonicalAnalyzer $canonicalAnalyzer;

    private RobotsMetaAnalyzer $robotsMetaAnalyzer;

    public function __construct(
        ?TitleAnalyzer $titleAnalyzer = null,
        ?MetaDescriptionAnalyzer $metaDescriptionAnalyzer = null,
        ?HeadingAnalyzer $headingAnalyzer = null,
        ?CanonicalAnalyzer $canonicalAnalyzer = null,
        ?RobotsMetaAnalyzer $robotsMetaAnalyzer = null
    ) {
        $this->titleAnalyzer =
            $titleAnalyzer ?? new TitleAnalyzer();

        $this->metaDescriptionAnalyzer =
            $metaDescriptionAnalyzer ?? new MetaDescriptionAnalyzer();

        $this->headingAnalyzer =
            $headingAnalyzer ?? new HeadingAnalyzer();

        $this->canonicalAnalyzer =
            $canonicalAnalyzer ?? new CanonicalAnalyzer();

        $this->robotsMetaAnalyzer =
            $robotsMetaAnalyzer ?? new RobotsMetaAnalyzer();
    }

    /**
     * Audit an HTML document.
     *
     * @param string $html HTML source to analyze.
     * @param string $url  Optional source URL.
     */
    public function audit(
        string $html,
        string $url = ''
    ): AuditResult {
        if (trim($html) === '') {
            throw new InvalidArgumentException(
                'HTML content cannot be empty.'
            );
        }

        /*
         * AuditResult accepts a nullable URL.
         *
         * An empty source URL therefore becomes null rather
         * than being exposed as an empty string.
         */
        $auditResult = new AuditResult(
            $url !== '' ? $url : null
        );

        /*
         * Page title.
         */
        $auditResult->addCheck(
            'title',
            $this->titleAnalyzer->analyze($html)
        );

        /*
         * Meta description.
         */
        $auditResult->addCheck(
            'meta_description',
            $this->metaDescriptionAnalyzer->analyze($html)
        );

        /*
         * Heading structure.
         */
        $auditResult->addCheck(
            'headings',
            $this->headingAnalyzer->analyze($html)
        );

        /*
         * Canonical URL.
         *
         * Supplying the page URL allows CanonicalAnalyzer
         * to determine whether the canonical is
         * self-referencing.
         */
        $auditResult->addCheck(
            'canonical',
            $this->canonicalAnalyzer->analyze(
                $html,
                $url !== '' ? $url : null
            )
        );

        /*
         * Robots meta directives.
         */
        $auditResult->addCheck(
            'robots',
            $this->robotsMetaAnalyzer->analyze($html)
        );

        return $auditResult;
    }
}
