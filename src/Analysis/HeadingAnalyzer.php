<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class HeadingAnalyzer
{
    /**
     * Analyze heading elements (H1-H6) in an HTML document.
     *
     * @return array{
     *     headings: array<int, array{level: int, text: string}>,
     *     counts: array<string, int>,
     *     total: int,
     *     h1_count: int,
     *     has_h1: bool,
     *     multiple_h1: bool,
     *     empty_count: int,
     *     hierarchy_valid: bool,
     *     status: string,
     *     message: string
     * }
     */
    public function analyze(string $html): array
    {
        if (trim($html) === '') {
            return $this->result(
                [],
                $this->emptyCounts(),
                0,
                0,
                false,
                false,
                0,
                true,
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
                [],
                $this->emptyCounts(),
                0,
                0,
                false,
                false,
                0,
                true,
                'error',
                'The HTML document could not be parsed.'
            );
        }

        /*
         * Remove the temporary processing instruction used to force
         * UTF-8 parsing.
         */
        foreach ($document->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $document->removeChild($node);
                break;
            }
        }

        $xpath = new DOMXPath($document);

        $nodes = $xpath->query(
            '//h1 | //h2 | //h3 | //h4 | //h5 | //h6'
        );

        if ($nodes === false || $nodes->length === 0) {
            return $this->result(
                [],
                $this->emptyCounts(),
                0,
                0,
                false,
                false,
                0,
                true,
                'error',
                'The page does not contain any heading elements.'
            );
        }

        $headings = [];
        $counts = $this->emptyCounts();
        $emptyCount = 0;

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($node->tagName);

            $level = (int) substr($tagName, 1);

            $text = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $node->textContent
                ) ?? ''
            );

            $counts[$tagName]++;

            if ($text === '') {
                $emptyCount++;
            }

            $headings[] = [
                'level' => $level,
                'text' => $text,
            ];
        }

        $total = count($headings);
        $h1Count = $counts['h1'];
        $hasH1 = $h1Count > 0;
        $multipleH1 = $h1Count > 1;
        $hierarchyValid = $this->isHierarchyValid($headings);

        if (!$hasH1) {
            return $this->result(
                $headings,
                $counts,
                $total,
                $h1Count,
                false,
                false,
                $emptyCount,
                $hierarchyValid,
                'error',
                'The page does not contain an H1 heading.'
            );
        }

        if ($multipleH1) {
            return $this->result(
                $headings,
                $counts,
                $total,
                $h1Count,
                true,
                true,
                $emptyCount,
                $hierarchyValid,
                'warning',
                'The page contains multiple H1 headings.'
            );
        }

        if ($emptyCount > 0) {
            return $this->result(
                $headings,
                $counts,
                $total,
                $h1Count,
                true,
                false,
                $emptyCount,
                $hierarchyValid,
                'warning',
                'The page contains one or more empty headings.'
            );
        }

        if (!$hierarchyValid) {
            return $this->result(
                $headings,
                $counts,
                $total,
                $h1Count,
                true,
                false,
                $emptyCount,
                false,
                'warning',
                'The heading hierarchy skips one or more levels.'
            );
        }

        return $this->result(
            $headings,
            $counts,
            $total,
            $h1Count,
            true,
            false,
            $emptyCount,
            true,
            'pass',
            'The page has a valid heading structure.'
        );
    }

    /**
     * Check whether heading levels progress without skipping levels.
     *
     * @param array<int, array{level: int, text: string}> $headings
     */
    private function isHierarchyValid(array $headings): bool
    {
        $previousLevel = null;

        foreach ($headings as $heading) {
            $currentLevel = $heading['level'];

            if (
                $previousLevel !== null &&
                $currentLevel > $previousLevel + 1
            ) {
                return false;
            }

            $previousLevel = $currentLevel;
        }

        return true;
    }

    /**
     * @return array{
     *     h1: int,
     *     h2: int,
     *     h3: int,
     *     h4: int,
     *     h5: int,
     *     h6: int
     * }
     */
    private function emptyCounts(): array
    {
        return [
            'h1' => 0,
            'h2' => 0,
            'h3' => 0,
            'h4' => 0,
            'h5' => 0,
            'h6' => 0,
        ];
    }

    /**
     * Build a consistent analysis result.
     *
     * @param array<int, array{level: int, text: string}> $headings
     * @param array<string, int> $counts
     *
     * @return array{
     *     headings: array<int, array{level: int, text: string}>,
     *     counts: array<string, int>,
     *     total: int,
     *     h1_count: int,
     *     has_h1: bool,
     *     multiple_h1: bool,
     *     empty_count: int,
     *     hierarchy_valid: bool,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        array $headings,
        array $counts,
        int $total,
        int $h1Count,
        bool $hasH1,
        bool $multipleH1,
        int $emptyCount,
        bool $hierarchyValid,
        string $status,
        string $message
    ): array {
        return [
            'headings' => $headings,
            'counts' => $counts,
            'total' => $total,
            'h1_count' => $h1Count,
            'has_h1' => $hasH1,
            'multiple_h1' => $multipleH1,
            'empty_count' => $emptyCount,
            'hierarchy_valid' => $hierarchyValid,
            'status' => $status,
            'message' => $message,
        ];
    }
}
