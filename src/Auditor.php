<?php

declare(strict_types=1);

namespace WebAuditKit;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;

/**
 * Core website auditing engine for WebAuditKit.
 *
 * Analyzes HTML documents and extracts common on-page
 * and technical SEO signals.
 */
final class Auditor
{
    /**
     * Audit an HTML document.
     *
     * @param string $html HTML source to analyze.
     * @param string $url  Optional source URL.
     *
     * @return array<string, mixed>
     */
    public function audit(string $html, string $url = ''): array
    {
        if (trim($html) === '') {
            throw new InvalidArgumentException(
                'HTML content cannot be empty.'
            );
        }

        $dom = new DOMDocument();

        $previous = libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            $html,
            LIBXML_NOWARNING | LIBXML_NOERROR
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            throw new InvalidArgumentException(
                'Unable to parse the supplied HTML.'
            );
        }

        $xpath = new DOMXPath($dom);

        $title = $this->getTitle($xpath);
        $description = $this->getMetaDescription($xpath);
        $headings = $this->getHeadings($xpath);
        $canonical = $this->getCanonical($xpath);
        $robots = $this->getRobots($xpath);
        $images = $this->getImageStats($xpath);
        $links = $this->getLinkStats($xpath, $url);

        return [
            'url' => $url,

            'title' => [
                'value' => $title,
                'length' => $this->stringLength($title),
                'exists' => $title !== null && $title !== '',
            ],

            'meta_description' => [
                'value' => $description,
                'length' => $this->stringLength($description),
                'exists' => $description !== null && $description !== '',
            ],

            'headings' => $headings,

            'canonical' => [
                'value' => $canonical,
                'exists' => $canonical !== null && $canonical !== '',
            ],

            'robots' => [
                'value' => $robots,
                'exists' => $robots !== null && $robots !== '',
                'noindex' => $this->containsDirective($robots, 'noindex'),
                'nofollow' => $this->containsDirective($robots, 'nofollow'),
            ],

            'images' => $images,

            'links' => $links,

            'summary' => $this->buildSummary(
                $title,
                $description,
                $headings,
                $canonical,
                $images
            ),
        ];
    }

    /**
     * Get the page title.
     */
    private function getTitle(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//title');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim($nodes->item(0)?->textContent ?? '');

        return $value !== '' ? $value : null;
    }

    /**
     * Get the meta description.
     */
    private function getMetaDescription(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query(
            '//meta[
                translate(
                    @name,
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                    "abcdefghijklmnopqrstuvwxyz"
                ) = "description"
            ]'
        );

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim(
            $nodes->item(0)?->getAttribute('content') ?? ''
        );

        return $value !== '' ? $value : null;
    }

    /**
     * Extract H1-H6 headings.
     *
     * @return array<string, array<int, string>>
     */
    private function getHeadings(DOMXPath $xpath): array
    {
        $headings = [];

        for ($level = 1; $level <= 6; $level++) {
            $key = 'h' . $level;
            $headings[$key] = [];

            $nodes = $xpath->query('//' . $key);

            if ($nodes === false) {
                continue;
            }

            foreach ($nodes as $node) {
                $text = trim($node->textContent);

                if ($text !== '') {
                    $headings[$key][] = $text;
                }
            }
        }

        return $headings;
    }

    /**
     * Get the canonical URL.
     */
    private function getCanonical(DOMXPath $xpath): ?string
    {
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
            return null;
        }

        $value = trim(
            $nodes->item(0)?->getAttribute('href') ?? ''
        );

        return $value !== '' ? $value : null;
    }

    /**
     * Get the robots meta directive.
     */
    private function getRobots(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query(
            '//meta[
                translate(
                    @name,
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                    "abcdefghijklmnopqrstuvwxyz"
                ) = "robots"
            ]'
        );

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim(
            $nodes->item(0)?->getAttribute('content') ?? ''
        );

        return $value !== '' ? $value : null;
    }

    /**
     * Analyze image alt attributes.
     *
     * @return array<string, int>
     */
    private function getImageStats(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('//img');

        if ($nodes === false) {
            return [
                'total' => 0,
                'with_alt' => 0,
                'missing_alt' => 0,
                'empty_alt' => 0,
            ];
        }

        $withAlt = 0;
        $missingAlt = 0;
        $emptyAlt = 0;

        foreach ($nodes as $node) {
            if (!$node->hasAttribute('alt')) {
                $missingAlt++;
                continue;
            }

            if (trim($node->getAttribute('alt')) === '') {
                $emptyAlt++;
                continue;
            }

            $withAlt++;
        }

        return [
            'total' => $nodes->length,
            'with_alt' => $withAlt,
            'missing_alt' => $missingAlt,
            'empty_alt' => $emptyAlt,
        ];
    }

    /**
     * Analyze links.
     *
     * @return array<string, int>
     */
    private function getLinkStats(
        DOMXPath $xpath,
        string $pageUrl
    ): array {
        $nodes = $xpath->query('//a[@href]');

        if ($nodes === false) {
            return [
                'total' => 0,
                'internal' => 0,
                'external' => 0,
                'nofollow' => 0,
            ];
        }

        $internal = 0;
        $external = 0;
        $nofollow = 0;

        $pageHost = parse_url($pageUrl, PHP_URL_HOST);

        foreach ($nodes as $node) {
            $href = trim($node->getAttribute('href'));

            if ($href === '') {
                continue;
            }

            $rel = strtolower(
                trim($node->getAttribute('rel'))
            );

            if (
                preg_match('/(?:^|\s)nofollow(?:\s|$)/i', $rel)
            ) {
                $nofollow++;
            }

            /*
             * Relative links are considered internal.
             */
            $linkHost = parse_url($href, PHP_URL_HOST);

            if ($linkHost === null || $linkHost === false) {
                $internal++;
                continue;
            }

            if (
                $pageHost !== null &&
                $pageHost !== false &&
                strcasecmp($pageHost, $linkHost) === 0
            ) {
                $internal++;
            } else {
                $external++;
            }
        }

        return [
            'total' => $nodes->length,
            'internal' => $internal,
            'external' => $external,
            'nofollow' => $nofollow,
        ];
    }

    /**
     * Create a simple audit summary.
     *
     * @param array<string, array<int, string>> $headings
     * @param array<string, int> $images
     *
     * @return array{
     *     passed: array<int, string>,
     *     warnings: array<int, string>
     * }
     */
    private function buildSummary(
        ?string $title,
        ?string $description,
        array $headings,
        ?string $canonical,
        array $images
    ): array {
        $passed = [];
        $warnings = [];

        if ($title === null) {
            $warnings[] = 'Page title is missing.';
        } else {
            $passed[] = 'Page title found.';

            $length = $this->stringLength($title);

            if ($length < 30) {
                $warnings[] = 'Page title may be too short.';
            } elseif ($length > 60) {
                $warnings[] = 'Page title may be too long.';
            }
        }

        if ($description === null) {
            $warnings[] = 'Meta description is missing.';
        } else {
            $passed[] = 'Meta description found.';

            $length = $this->stringLength($description);

            if ($length < 70) {
                $warnings[] =
                    'Meta description may be too short.';
            } elseif ($length > 160) {
                $warnings[] =
                    'Meta description may be too long.';
            }
        }

        $h1Count = count($headings['h1'] ?? []);

        if ($h1Count === 0) {
            $warnings[] = 'No H1 heading found.';
        } elseif ($h1Count === 1) {
            $passed[] = 'One H1 heading found.';
        } else {
            $warnings[] =
                'Multiple H1 headings were found.';
        }

        if ($canonical === null) {
            $warnings[] = 'Canonical URL is missing.';
        } else {
            $passed[] = 'Canonical URL found.';
        }

        $missingAlt =
            ($images['missing_alt'] ?? 0) +
            ($images['empty_alt'] ?? 0);

        if ($missingAlt > 0) {
            $warnings[] =
                $missingAlt .
                ' image(s) have missing or empty alt attributes.';
        } else {
            $passed[] =
                'All detected images have non-empty alt attributes.';
        }

        return [
            'passed' => $passed,
            'warnings' => $warnings,
        ];
    }

    /**
     * Determine whether a robots directive exists.
     */
    private function containsDirective(
        ?string $robots,
        string $directive
    ): bool {
        if ($robots === null) {
            return false;
        }

        $directives = array_map(
            static fn (string $value): string =>
                strtolower(trim($value)),
            explode(',', $robots)
        );

        return in_array(
            strtolower($directive),
            $directives,
            true
        );
    }

    /**
     * Safely calculate string length.
     */
    private function stringLength(?string $value): int
    {
        if ($value === null) {
            return 0;
        }

        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }
}
