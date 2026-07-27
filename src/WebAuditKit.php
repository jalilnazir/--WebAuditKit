<?php

declare(strict_types=1);

namespace WebAuditKit;

use WebAuditKit\Http\PageFetcher;

final class WebAuditKit
{
    private PageFetcher $fetcher;
    private Auditor $auditor;

    public function __construct(
        ?PageFetcher $fetcher = null,
        ?Auditor $auditor = null
    ) {
        $this->fetcher = $fetcher ?? new PageFetcher();
        $this->auditor = $auditor ?? new Auditor();
    }

    /**
     * Audit a live website URL.
     *
     * @return array<string, mixed>
     */
    public function auditUrl(string $url): array
    {
        $html = $this->fetcher->fetch($url);

        return $this->auditor->audit($html, $url);
    }

    /**
     * Audit HTML that has already been retrieved.
     *
     * @return array<string, mixed>
     */
    public function auditHtml(
        string $html,
        string $url = ''
    ): array {
        return $this->auditor->audit($html, $url);
    }
}
