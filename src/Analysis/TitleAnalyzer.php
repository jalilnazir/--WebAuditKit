<?php

declare(strict_types=1);

namespace WebAuditKit\Analysis;

final class TitleAnalyzer
{
    public const MIN_RECOMMENDED_LENGTH = 30;
    public const MAX_RECOMMENDED_LENGTH = 60;

    /**
     * Analyze the <title> element of an HTML document.
     *
     * @return array{
     *     title: ?string,
     *     exists: bool,
     *     length: int,
     *     status: string
     * }
     */
    public function analyze(string $html): array
    {
        $title = $this->extractTitle($html);

        if ($title === null) {
            return [
                'title' => null,
                'exists' => false,
                'length' => 0,
                'status' => 'missing',
            ];
        }

        $length = $this->length($title);

        if ($length < self::MIN_RECOMMENDED_LENGTH) {
            $status = 'too_short';
        } elseif ($length > self::MAX_RECOMMENDED_LENGTH) {
            $status = 'too_long';
        } else {
            $status = 'good';
        }

        return [
            'title' => $title,
            'exists' => true,
            'length' => $length,
            'status' => $status,
        ];
    }

    public function extractTitle(string $html): ?string
    {
        if (!preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return null;
        }

        $title = html_entity_decode(
            strip_tags($matches[1]),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
        $title = trim($title);

        return $title === '' ? null : $title;
    }

    private function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}
