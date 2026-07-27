<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMXPath;

final class TitleAnalyzer
{
    private const RECOMMENDED_MIN_LENGTH = 30;
    private const RECOMMENDED_MAX_LENGTH = 60;

    /**
     * Analyze the <title> element of an HTML document.
     *
     * @return array{
     *     title: ?string,
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
            /*
             * DOMDocument's HTML parser may interpret UTF-8 content using
             * the wrong encoding when the document does not explicitly
             * declare one. Prefixing the document with an XML encoding
             * declaration ensures Unicode titles are decoded correctly.
             */
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

        /*
         * Remove the temporary XML processing instruction that was added
         * only to force UTF-8 parsing.
         */
        foreach ($document->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $document->removeChild($node);
                break;
            }
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//title');

        if ($nodes === false || $nodes->length === 0) {
            return $this->result(
                null,
                false,
                0,
                'error',
                'The page does not contain a title element.'
            );
        }

        $title = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $nodes->item(0)?->textContent ?? ''
            ) ?? ''
        );

        if ($title === '') {
            return $this->result(
                '',
                true,
                0,
                'error',
                'The page title is empty.'
            );
        }

        /*
         * Count Unicode characters rather than raw bytes.
         */
        $length = mb_strlen($title, 'UTF-8');

        if ($length < self::RECOMMENDED_MIN_LENGTH) {
            return $this->result(
                $title,
                true,
                $length,
                'warning',
                'The page title is shorter than the recommended range.'
            );
        }

        if ($length > self::RECOMMENDED_MAX_LENGTH) {
            return $this->result(
                $title,
                true,
                $length,
                'warning',
                'The page title is longer than the recommended range.'
            );
        }

        return $this->result(
            $title,
            true,
            $length,
            'pass',
            'The page title is present and within the recommended length range.'
        );
    }

    /**
     * Build a consistent analysis result.
     *
     * @return array{
     *     title: ?string,
     *     exists: bool,
     *     length: int,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        ?string $title,
        bool $exists,
        int $length,
        string $status,
        string $message
    ): array {
        return [
            'title' => $title,
            'exists' => $exists,
            'length' => $length,
            'status' => $status,
            'message' => $message,
        ];
    }
}
