<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMXPath;

final class MetaDescriptionAnalyzer
{
    private const RECOMMENDED_MIN_LENGTH = 120;
    private const RECOMMENDED_MAX_LENGTH = 160;

    /**
     * Analyze the meta description of an HTML document.
     *
     * @return array{
     *     description: ?string,
     *     exists: bool,
     *     length: int,
     *     status: string,
     *     message: string
     * }
     */
    public function analyze(string $html): array
    {
        if (trim($html) === '') {
            return $this->result(
                null,
                false,
                0,
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

        $nodes = $xpath->query(
            '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "description"]'
        );

        if ($nodes === false || $nodes->length === 0) {
            return $this->result(
                null,
                false,
                0,
                'error',
                'The page does not contain a meta description.'
            );
        }

        $description = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $nodes->item(0)?->getAttribute('content') ?? ''
            ) ?? ''
        );

        if ($description === '') {
            return $this->result(
                '',
                true,
                0,
                'error',
                'The meta description is empty.'
            );
        }

        $length = mb_strlen($description, 'UTF-8');

        if ($length < self::RECOMMENDED_MIN_LENGTH) {
            return $this->result(
                $description,
                true,
                $length,
                'warning',
                'The meta description is shorter than the recommended range.'
            );
        }

        if ($length > self::RECOMMENDED_MAX_LENGTH) {
            return $this->result(
                $description,
                true,
                $length,
                'warning',
                'The meta description is longer than the recommended range.'
            );
        }

        return $this->result(
            $description,
            true,
            $length,
            'pass',
            'The meta description is present and within the recommended length range.'
        );
    }

    /**
     * Build a consistent analysis result.
     *
     * @return array{
     *     description: ?string,
     *     exists: bool,
     *     length: int,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        ?string $description,
        bool $exists,
        int $length,
        string $status,
        string $message
    ): array {
        return [
            'description' => $description,
            'exists' => $exists,
            'length' => $length,
            'status' => $status,
            'message' => $message,
        ];
    }
}
