<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class CanonicalAnalyzer
{
    /**
     * Analyze canonical link elements in an HTML document.
     *
     * @return array{
     *     canonical: ?string,
     *     exists: bool,
     *     count: int,
     *     multiple: bool,
     *     empty: bool,
     *     absolute: bool,
     *     valid_url: bool,
     *     self_referencing: ?bool,
     *     status: string,
     *     message: string
     * }
     */
    public function analyze(
        string $html,
        ?string $pageUrl = null
    ): array {
        if (trim($html) === '') {
            return $this->result(
                null,
                false,
                0,
                false,
                false,
                false,
                false,
                null,
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
            return $this->result(
                null,
                false,
                0,
                false,
                false,
                false,
                false,
                null,
                'error',
                'The HTML document could not be parsed.'
            );
        }

        foreach ($document->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $document->removeChild($node);
                break;
            }
        }

        $xpath = new DOMXPath($document);

        /*
         * rel is a space-separated token list, so this expression
         * detects "canonical" regardless of token order or case.
         */
        $nodes = $xpath->query(
            '//link[
                contains(
                    concat(
                        " ",
                        translate(
                            normalize-space(@rel),
                            "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                            "abcdefghijklmnopqrstuvwxyz"
                        ),
                        " "
                    ),
                    " canonical "
                )
            ]'
        );

        if ($nodes === false || $nodes->length === 0) {
            return $this->result(
                null,
                false,
                0,
                false,
                false,
                false,
                false,
                null,
                'warning',
                'The page does not contain a canonical link.'
            );
        }

        $count = $nodes->length;
        $multiple = $count > 1;

        $first = $nodes->item(0);

        if (!$first instanceof DOMElement) {
            return $this->result(
                null,
                false,
                $count,
                $multiple,
                false,
                false,
                false,
                null,
                'error',
                'The canonical link could not be analyzed.'
            );
        }

        $canonical = trim(
            $first->getAttribute('href')
        );

        $empty = $canonical === '';

        if ($empty) {
            return $this->result(
                '',
                true,
                $count,
                $multiple,
                true,
                false,
                false,
                null,
                'error',
                'The canonical link has an empty href attribute.'
            );
        }

        $absolute = $this->isAbsoluteHttpUrl(
            $canonical
        );

        $validUrl = $absolute &&
            filter_var(
                $canonical,
                FILTER_VALIDATE_URL
            ) !== false;

        $selfReferencing = null;

        if (
            $validUrl &&
            $pageUrl !== null &&
            trim($pageUrl) !== ''
        ) {
            $selfReferencing = $this->normalizeUrl($canonical)
                === $this->normalizeUrl($pageUrl);
        }

        if ($multiple) {
            return $this->result(
                $canonical,
                true,
                $count,
                true,
                false,
                $absolute,
                $validUrl,
                $selfReferencing,
                'error',
                'The page contains multiple canonical links.'
            );
        }

        if (!$absolute || !$validUrl) {
            return $this->result(
                $canonical,
                true,
                $count,
                false,
                false,
                $absolute,
                $validUrl,
                $selfReferencing,
                'warning',
                'The canonical URL should be a valid absolute HTTP or HTTPS URL.'
            );
        }

        return $this->result(
            $canonical,
            true,
            $count,
            false,
            false,
            true,
            true,
            $selfReferencing,
            'pass',
            'The page contains a valid canonical URL.'
        );
    }

    private function isAbsoluteHttpUrl(
        string $url
    ): bool {
        $scheme = strtolower(
            (string) parse_url(
                $url,
                PHP_URL_SCHEME
            )
        );

        $host = parse_url(
            $url,
            PHP_URL_HOST
        );

        return (
            $scheme === 'http' ||
            $scheme === 'https'
        ) &&
            is_string($host) &&
            $host !== '';
    }

    private function normalizeUrl(
        string $url
    ): string {
        $url = trim($url);

        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        $scheme = isset($parts['scheme'])
            ? strtolower($parts['scheme'])
            : '';

        $host = isset($parts['host'])
            ? strtolower($parts['host'])
            : '';

        $port = isset($parts['port'])
            ? ':' . $parts['port']
            : '';

        $path = $parts['path'] ?? '/';

        if ($path === '') {
            $path = '/';
        }

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $query = isset($parts['query'])
            ? '?' . $parts['query']
            : '';

        return $scheme .
            '://' .
            $host .
            $port .
            $path .
            $query;
    }

    /**
     * Build a consistent canonical analysis result.
     *
     * @return array{
     *     canonical: ?string,
     *     exists: bool,
     *     count: int,
     *     multiple: bool,
     *     empty: bool,
     *     absolute: bool,
     *     valid_url: bool,
     *     self_referencing: ?bool,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        ?string $canonical,
        bool $exists,
        int $count,
        bool $multiple,
        bool $empty,
        bool $absolute,
        bool $validUrl,
        ?bool $selfReferencing,
        string $status,
        string $message
    ): array {
        return [
            'canonical' => $canonical,
            'exists' => $exists,
            'count' => $count,
            'multiple' => $multiple,
            'empty' => $empty,
            'absolute' => $absolute,
            'valid_url' => $validUrl,
            'self_referencing' => $selfReferencing,
            'status' => $status,
            'message' => $message,
        ];
    }
}
