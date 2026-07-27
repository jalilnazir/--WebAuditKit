<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ImageAnalyzer
{
    /**
     * Analyze image elements in an HTML document.
     *
     * @return array{
     *     images: array<int, array{
     *         src: string,
     *         alt: ?string,
     *         has_alt: bool,
     *         empty_alt: bool,
     *         has_dimensions: bool,
     *         lazy_loaded: bool
     *     }>,
     *     total: int,
     *     missing_alt: int,
     *     empty_alt: int,
     *     missing_dimensions: int,
     *     lazy_loaded: int,
     *     status: string,
     *     message: string
     * }
     */
    public function analyze(string $html): array
    {
        if (trim($html) === '') {
            return $this->result(
                [],
                0,
                0,
                0,
                0,
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
                [],
                0,
                0,
                0,
                0,
                0,
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
        $nodes = $xpath->query('//img');

        if ($nodes === false || $nodes->length === 0) {
            return $this->result(
                [],
                0,
                0,
                0,
                0,
                0,
                'pass',
                'The page does not contain any images.'
            );
        }

        $images = [];
        $missingAlt = 0;
        $emptyAlt = 0;
        $missingDimensions = 0;
        $lazyLoaded = 0;

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $src = trim($node->getAttribute('src'));

            $hasAlt = $node->hasAttribute('alt');
            $alt = $hasAlt
                ? trim($node->getAttribute('alt'))
                : null;

            $isEmptyAlt = $hasAlt && $alt === '';

            $hasWidth = $node->hasAttribute('width')
                && trim($node->getAttribute('width')) !== '';

            $hasHeight = $node->hasAttribute('height')
                && trim($node->getAttribute('height')) !== '';

            $hasDimensions = $hasWidth && $hasHeight;

            $loading = strtolower(
                trim($node->getAttribute('loading'))
            );

            $isLazyLoaded = $loading === 'lazy';

            if (!$hasAlt) {
                $missingAlt++;
            }

            if ($isEmptyAlt) {
                $emptyAlt++;
            }

            if (!$hasDimensions) {
                $missingDimensions++;
            }

            if ($isLazyLoaded) {
                $lazyLoaded++;
            }

            $images[] = [
                'src' => $src,
                'alt' => $alt,
                'has_alt' => $hasAlt,
                'empty_alt' => $isEmptyAlt,
                'has_dimensions' => $hasDimensions,
                'lazy_loaded' => $isLazyLoaded,
            ];
        }

        $total = count($images);

        if ($missingAlt > 0) {
            return $this->result(
                $images,
                $total,
                $missingAlt,
                $emptyAlt,
                $missingDimensions,
                $lazyLoaded,
                'error',
                'One or more images are missing an alt attribute.'
            );
        }

        if ($missingDimensions > 0) {
            return $this->result(
                $images,
                $total,
                $missingAlt,
                $emptyAlt,
                $missingDimensions,
                $lazyLoaded,
                'warning',
                'One or more images are missing width or height attributes.'
            );
        }

        return $this->result(
            $images,
            $total,
            $missingAlt,
            $emptyAlt,
            $missingDimensions,
            $lazyLoaded,
            'pass',
            'The page images passed the basic image checks.'
        );
    }

    /**
     * Build a consistent analysis result.
     *
     * @param array<int, array{
     *     src: string,
     *     alt: ?string,
     *     has_alt: bool,
     *     empty_alt: bool,
     *     has_dimensions: bool,
     *     lazy_loaded: bool
     * }> $images
     *
     * @return array{
     *     images: array<int, array{
     *         src: string,
     *         alt: ?string,
     *         has_alt: bool,
     *         empty_alt: bool,
     *         has_dimensions: bool,
     *         lazy_loaded: bool
     *     }>,
     *     total: int,
     *     missing_alt: int,
     *     empty_alt: int,
     *     missing_dimensions: int,
     *     lazy_loaded: int,
     *     status: string,
     *     message: string
     * }
     */
    private function result(
        array $images,
        int $total,
        int $missingAlt,
        int $emptyAlt,
        int $missingDimensions,
        int $lazyLoaded,
        string $status,
        string $message
    ): array {
        return [
            'images' => $images,
            'total' => $total,
            'missing_alt' => $missingAlt,
            'empty_alt' => $emptyAlt,
            'missing_dimensions' => $missingDimensions,
            'lazy_loaded' => $lazyLoaded,
            'status' => $status,
            'message' => $message,
        ];
    }
}
