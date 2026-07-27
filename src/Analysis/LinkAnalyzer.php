<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class LinkAnalyzer
{
    /**
     * Analyze links in an HTML document.
     *
     * @return array{
     *     links: array<int, array{
     *         href: ?string,
     *         text: string,
     *         type: string,
     *         has_href: bool,
     *         empty_href: bool,
     *         nofollow: bool,
     *         sponsored: bool,
     *         ugc: bool
     *     }>,
     *     total: int,
     *     internal: int,
     *     external: int,
     *     anchors: int,
     *     mailto: int,
     *     tel: int,
     *     javascript: int,
     *     missing_href: int,
     *     empty_href: int,
     *     nofollow: int,
     *     sponsored: int,
     *     ugc: int,
     *     status: string,
     *     message: string
     * }
     */
    public function analyze(string $html, ?string $baseUrl = null): array
    {
        if (trim($html) === '') {
            return $this->emptyResult(
                'error',
                'The HTML document is empty.'
            );
        }

        $document = new DOMDocument();

        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_NOERROR | LIBXML_NOWARNING
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded === false) {
            return $this->emptyResult(
                'error',
                'The HTML document could not be parsed.'
            );
        }

        /*
         * Remove the temporary processing instruction used
         * to force UTF-8 parsing.
         */
        foreach ($document->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $document->removeChild($node);
                break;
            }
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//a');

        if ($nodes === false || $nodes->length === 0) {
            return $this->emptyResult(
                'pass',
                'The page does not contain any links.'
            );
        }

        $links = [];

        $internal = 0;
        $external = 0;
        $anchors = 0;
        $mailto = 0;
        $tel = 0;
        $javascript = 0;
        $missingHref = 0;
        $emptyHref = 0;
        $nofollow = 0;
        $sponsored = 0;
        $ugc = 0;

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $hasHref = $node->hasAttribute('href');

            $href = $hasHref
                ? trim($node->getAttribute('href'))
                : null;

            $isEmptyHref = $hasHref && $href === '';

            $text = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $node->textContent
                ) ?? ''
            );

            $rel = strtolower(
                trim($node->getAttribute('rel'))
            );

            $relValues = preg_split(
                '/\s+/u',
                $rel,
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: [];

            $isNofollow = in_array(
                'nofollow',
                $relValues,
                true
            );

            $isSponsored = in_array(
                'sponsored',
                $relValues,
                true
            );

            $isUgc = in_array(
                'ugc',
                $relValues,
                true
            );

            $type = $this->determineType(
                $href,
                $baseUrl
            );

            switch ($type) {
                case 'internal':
                    $internal++;
                    break;

                case 'external':
                    $external++;
                    break;

                case 'anchor':
                    $anchors++;
                    break;

                case 'mailto':
                    $mailto++;
                    break;

                case 'tel':
                    $tel++;
                    break;

                case 'javascript':
                    $javascript++;
                    break;
            }

            if (!$hasHref) {
                $missingHref++;
            }

            if ($isEmptyHref) {
                $emptyHref++;
            }

            if ($isNofollow) {
                $nofollow++;
            }

            if ($isSponsored) {
                $sponsored++;
            }

            if ($isUgc) {
                $ugc++;
            }

            $links[] = [
                'href' => $href,
                'text' => $text,
                'type' => $type,
                'has_href' => $hasHref,
                'empty_href' => $isEmptyHref,
                'nofollow' => $isNofollow,
                'sponsored' => $isSponsored,
                'ugc' => $isUgc,
            ];
        }

        $total = count($links);

        /*
         * JavaScript URLs are treated as the highest-priority
         * link issue.
         */
        if ($javascript > 0) {
            return $this->result(
                $links,
                $total,
                $internal,
                $external,
                $anchors,
                $mailto,
                $tel,
                $javascript,
                $missingHref,
                $emptyHref,
                $nofollow,
                $sponsored,
                $ugc,
                'error',
                'One or more links use a JavaScript URL.'
            );
        }

        /*
         * Missing or empty href attributes are warnings.
         */
        if ($missingHref > 0 || $emptyHref > 0) {
            return $this->result(
                $links,
                $total,
                $internal,
                $external,
                $anchors,
                $mailto,
                $tel,
                $javascript,
                $missingHref,
                $emptyHref,
                $nofollow,
                $sponsored,
                $ugc,
                'warning',
                'One or more links have a missing or empty href attribute.'
            );
        }

        return $this->result(
            $links,
            $total,
            $internal,
            $external,
            $anchors,
            $mailto,
            $tel,
            $javascript,
            $missingHref,
            $emptyHref,
            $nofollow,
            $sponsored,
            $ugc,
            'pass',
            'The page links passed the basic link checks.'
        );
    }

    /**
     * Determine the type of a link.
     */
    private function determineType(
        ?string $href,
        ?string $baseUrl
    ): string {
        if ($href === null) {
            return 'missing';
        }

        if ($href === '') {
            return 'empty';
        }

        /*
         * Fragment / anchor link.
         */
        if (str_starts_with($href, '#')) {
            return 'anchor';
        }

        /*
         * Email link.
         */
        if (preg_match('/^mailto:/i', $href) === 1) {
            return 'mailto';
        }

        /*
         * Telephone link.
         */
        if (preg_match('/^tel:/i', $href) === 1) {
            return 'tel';
        }

        /*
         * JavaScript URL.
         */
        if (preg_match('/^javascript:/i', $href) === 1) {
            return 'javascript';
        }

        /*
         * Protocol-relative URLs must be checked BEFORE
         * root-relative URLs.
         *
         * Example:
         *
         * //example.com/page
         *
         * also starts with "/", so checking "/" first would
         * incorrectly classify it as an internal relative URL.
         */
        if (str_starts_with($href, '//')) {
            $hrefHost = parse_url(
                'https:' . $href,
                PHP_URL_HOST
            );

            $baseHost = $this->getHost($baseUrl);

            if (
                $baseHost !== null &&
                $hrefHost !== null &&
                strcasecmp($hrefHost, $baseHost) === 0
            ) {
                return 'internal';
            }

            return 'external';
        }

        /*
         * Root-relative and relative URLs are internal.
         */
        if (
            str_starts_with($href, '/') ||
            str_starts_with($href, './') ||
            str_starts_with($href, '../') ||
            preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) !== 1
        ) {
            return 'internal';
        }

        /*
         * Absolute HTTP/HTTPS URLs.
         */
        $scheme = strtolower(
            (string) parse_url(
                $href,
                PHP_URL_SCHEME
            )
        );

        if ($scheme === 'http' || $scheme === 'https') {
            $hrefHost = parse_url(
                $href,
                PHP_URL_HOST
            );

            $baseHost = $this->getHost($baseUrl);

            if (
                $baseHost !== null &&
                $hrefHost !== null &&
                strcasecmp($hrefHost, $baseHost) === 0
            ) {
                return 'internal';
            }

            return 'external';
        }

        /*
         * Any scheme not explicitly handled above.
         */
        return 'other';
    }

    /**
     * Extract a hostname from the base URL.
     */
    private function getHost(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $host = parse_url(
            $url,
            PHP_URL_HOST
        );

        return is_string($host) && $host !== ''
            ? $host
            : null;
    }

    /**
     * Build the analysis result.
     *
     * @param array<int, array{
     *     href: ?string,
     *     text: string,
     *     type: string,
     *     has_href: bool,
     *     empty_href: bool,
     *     nofollow: bool,
     *     sponsored: bool,
     *     ugc: bool
     * }> $links
     *
     * @return array{
     *     links: array<int, array{
     *         href: ?string,
     *         text: string,
     *         type: string,
     *         has_href: bool,
     *         empty_href: bool,
     *         nofollow: bool,
     *         sponsored: bool,
     *         ugc: bool
     *     }>,
     *     total: int,
     *     internal: int,
     *     external: int,
     *     anchors: int,
     *     mailto: int,
     *     tel: int,
     *     javascript: int,
     *     missing_href: int,
     *     empty_href: int,
     *     nofollow: int,
     *     sponsored: int,
     *     ugc: int,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        array $links,
        int $total,
        int $internal,
        int $external,
        int $anchors,
        int $mailto,
        int $tel,
        int $javascript,
        int $missingHref,
        int $emptyHref,
        int $nofollow,
        int $sponsored,
        int $ugc,
        string $status,
        string $message
    ): array {
        return [
            'links' => $links,
            'total' => $total,
            'internal' => $internal,
            'external' => $external,
            'anchors' => $anchors,
            'mailto' => $mailto,
            'tel' => $tel,
            'javascript' => $javascript,
            'missing_href' => $missingHref,
            'empty_href' => $emptyHref,
            'nofollow' => $nofollow,
            'sponsored' => $sponsored,
            'ugc' => $ugc,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Build an empty analysis result.
     */
    private function emptyResult(
        string $status,
        string $message
    ): array {
        return $this->result(
            [],
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            $status,
            $message
        );
    }
}
