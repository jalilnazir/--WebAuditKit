<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class RobotsMetaAnalyzer
{
    /**
     * Analyze robots meta directives in an HTML document.
     *
     * @return array{
     *     exists: bool,
     *     count: int,
     *     content: ?string,
     *     directives: array<int, string>,
     *     indexable: bool,
     *     followable: bool,
     *     noarchive: bool,
     *     nosnippet: bool,
     *     noimageindex: bool,
     *     conflicting: bool,
     *     status: string,
     *     message: string
     * }
     */
    public function analyze(string $html): array
    {
        if (trim($html) === '') {
            return $this->result(
                false,
                0,
                null,
                [],
                true,
                true,
                false,
                false,
                false,
                false,
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
                false,
                0,
                null,
                [],
                true,
                true,
                false,
                false,
                false,
                false,
                'error',
                'The HTML document could not be parsed.'
            );
        }

        /*
         * Remove the temporary XML processing instruction used
         * to force UTF-8 parsing.
         */
        foreach ($document->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $document->removeChild($node);
                break;
            }
        }

        $xpath = new DOMXPath($document);

        /*
         * Meta name matching is case-insensitive.
         */
        $nodes = $xpath->query(
            '//meta[
                translate(
                    normalize-space(@name),
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                    "abcdefghijklmnopqrstuvwxyz"
                ) = "robots"
            ]'
        );

        if ($nodes === false || $nodes->length === 0) {
            /*
             * No robots meta tag means normal crawler defaults:
             *
             * index, follow
             */
            return $this->result(
                false,
                0,
                null,
                [],
                true,
                true,
                false,
                false,
                false,
                false,
                'pass',
                'The page does not contain a robots meta tag and defaults to index, follow.'
            );
        }

        $count = $nodes->length;

        $contents = [];
        $directives = [];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $content = trim(
                $node->getAttribute('content')
            );

            $contents[] = $content;

            if ($content === '') {
                continue;
            }

            /*
             * Robots directives are normally comma-separated,
             * but whitespace is also commonly encountered.
             */
            $tokens = preg_split(
                '/[\s,]+/u',
                strtolower($content),
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: [];

            foreach ($tokens as $token) {
                $token = trim($token);

                if (
                    $token !== '' &&
                    !in_array($token, $directives, true)
                ) {
                    $directives[] = $token;
                }
            }
        }

        $content = implode(', ', array_filter(
            $contents,
            static fn (string $value): bool => $value !== ''
        ));

        if ($content === '') {
            return $this->result(
                true,
                $count,
                '',
                [],
                true,
                true,
                false,
                false,
                false,
                false,
                'warning',
                'The robots meta tag has an empty content attribute.'
            );
        }

        $hasIndex = in_array(
            'index',
            $directives,
            true
        );

        $hasNoindex = in_array(
            'noindex',
            $directives,
            true
        );

        $hasFollow = in_array(
            'follow',
            $directives,
            true
        );

        $hasNofollow = in_array(
            'nofollow',
            $directives,
            true
        );

        $hasNoarchive = in_array(
            'noarchive',
            $directives,
            true
        );

        $hasNosnippet = in_array(
            'nosnippet',
            $directives,
            true
        );

        $hasNoimageindex = in_array(
            'noimageindex',
            $directives,
            true
        );

        /*
         * "none" is equivalent to:
         *
         * noindex, nofollow
         */
        $hasNone = in_array(
            'none',
            $directives,
            true
        );

        /*
         * "all" represents the normal:
         *
         * index, follow
         */
        $hasAll = in_array(
            'all',
            $directives,
            true
        );

        $indexable = !$hasNoindex && !$hasNone;
        $followable = !$hasNofollow && !$hasNone;

        /*
         * Detect contradictory directives.
         */
        $indexConflict =
            ($hasIndex && $hasNoindex) ||
            ($hasAll && ($hasNoindex || $hasNone));

        $followConflict =
            ($hasFollow && $hasNofollow) ||
            ($hasAll && ($hasNofollow || $hasNone));

        $noneConflict =
            $hasNone &&
            ($hasIndex || $hasFollow || $hasAll);

        $conflicting =
            $indexConflict ||
            $followConflict ||
            $noneConflict;

        if ($conflicting) {
            return $this->result(
                true,
                $count,
                $content,
                $directives,
                $indexable,
                $followable,
                $hasNoarchive,
                $hasNosnippet,
                $hasNoimageindex,
                true,
                'error',
                'The robots meta tag contains conflicting directives.'
            );
        }

        if (!$indexable) {
            return $this->result(
                true,
                $count,
                $content,
                $directives,
                false,
                $followable,
                $hasNoarchive,
                $hasNosnippet,
                $hasNoimageindex,
                false,
                'warning',
                'The page contains a noindex robots directive.'
            );
        }

        if (!$followable) {
            return $this->result(
                true,
                $count,
                $content,
                $directives,
                true,
                false,
                $hasNoarchive,
                $hasNosnippet,
                $hasNoimageindex,
                false,
                'warning',
                'The page contains a nofollow robots directive.'
            );
        }

        return $this->result(
            true,
            $count,
            $content,
            $directives,
            true,
            true,
            $hasNoarchive,
            $hasNosnippet,
            $hasNoimageindex,
            false,
            'pass',
            'The robots meta directives allow indexing and link following.'
        );
    }

    /**
     * Build a consistent robots meta analysis result.
     *
     * @param array<int, string> $directives
     *
     * @return array{
     *     exists: bool,
     *     count: int,
     *     content: ?string,
     *     directives: array<int, string>,
     *     indexable: bool,
     *     followable: bool,
     *     noarchive: bool,
     *     nosnippet: bool,
     *     noimageindex: bool,
     *     conflicting: bool,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        bool $exists,
        int $count,
        ?string $content,
        array $directives,
        bool $indexable,
        bool $followable,
        bool $noarchive,
        bool $nosnippet,
        bool $noimageindex,
        bool $conflicting,
        string $status,
        string $message
    ): array {
        return [
            'exists' => $exists,
            'count' => $count,
            'content' => $content,
            'directives' => $directives,
            'indexable' => $indexable,
            'followable' => $followable,
            'noarchive' => $noarchive,
            'nosnippet' => $nosnippet,
            'noimageindex' => $noimageindex,
            'conflicting' => $conflicting,
            'status' => $status,
            'message' => $message,
        ];
    }
}
