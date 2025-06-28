<?php
declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CustomExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', [$this, 'truncateFilter']),
            new TwigFilter('striptags', [$this, 'stripTagsFilter']),
        ];
    }

    /**
     * Truncate a string to a specified length
     * 
     * @param string $text The input text
     * @param int $length Maximum length of the truncated text
     * @param string $end Ending character(s) to append
     * @return string Truncated text
     */
    public function truncateFilter(?string $text, int $length = 100, string $end = '...'): string
    {
        // Handle null input
        if ($text === null) {
            return '';
        }

        // Remove HTML tags before truncating
        $text = strip_tags($text);

        // If text is shorter than length, return full text
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        // Truncate text
        $truncated = mb_substr($text, 0, $length);

        // Find the last space to avoid cutting words
        $last_space = mb_strrpos($truncated, ' ');
        if ($last_space !== false) {
            $truncated = mb_substr($truncated, 0, $last_space);
        }

        return $truncated . $end;
    }

    /**
     * Strip HTML tags from a string
     * 
     * @param ?string $text The input text
     * @return string Text without HTML tags
     */
    public function stripTagsFilter(?string $text): string
    {
        // Handle null input
        if ($text === null) {
            return '';
        }

        return strip_tags($text);
    }
}
