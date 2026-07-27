<?php

declare(strict_types=1);

namespace WebAuditKit;

use WebAuditKit\Http\PageFetcher;
use WebAuditKit\Result\AuditResult;

/**
 * High-level public API for WebAuditKit.
 *
 * Provides convenient methods for auditing supplied HTML
 * documents and live public URLs.
 */
final class WebAuditKit
{
    private Auditor $auditor;

    private PageFetcher $pageFetcher;

    public function __construct(
        ?Auditor $auditor = null,
        ?PageFetcher $pageFetcher = null
    ) {
        $this->auditor = $auditor ?? new Auditor();

        $this->pageFetcher =
            $pageFetcher ?? new PageFetcher();
    }

    /**
     * Audit an existing HTML document.
     *
     * @param string $html HTML source to analyze.
     * @param string $url  Optional source URL.
     */
    public function auditHtml(
        string $html,
        string $url = ''
    ): AuditResult {
        return $this->auditor->audit(
            $html,
            $url
        );
    }

    /**
     * Audit a live public URL.
     *
     * The page is fetched through PageFetcher, which performs
     * URL validation, SSRF protection, redirect validation,
     * timeout handling, response-size protection, and other
     * HTTP safety checks.
     */
    public function auditUrl(string $url): AuditResult
    {
        $html = $this->pageFetcher->fetch($url);

        return $this->auditor->audit(
            $html,
            $url
        );
    }
}
