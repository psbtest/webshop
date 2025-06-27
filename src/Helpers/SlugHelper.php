<?php
declare(strict_types=1);

namespace App\Helpers;

class SlugHelper
{
    /**
     * Generate URL-friendly slug
     */
    public static function create_slug(string $text, string $separator = '-'): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Remove accents and special characters
        $text = StringHelper::remove_accents($text);
        
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace any non-alphanumeric character with separator
        $text = preg_replace('/[^a-z0-9]+/', $separator, $text);
        
        // Remove multiple separators
        $text = preg_replace('/' . preg_quote($separator) . '+/', $separator, $text);
        
        // Trim separators from beginning and end
        $text = trim($text, $separator);
        
        return empty($text) ? 'untitled' : $text;
    }

    /**
     * Create unique slug by checking database
     */
    public static function create_unique_slug(string $text, callable $exists_callback, ?int $exclude_id = null): string
    {
        $base_slug = self::create_slug($text);
        $slug = $base_slug;
        $counter = 1;
        
        while ($exists_callback($slug, $exclude_id)) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Generate slug from title with fallback
     */
    public static function generate_from_title(string $title, string $fallback = 'untitled'): string
    {
        $slug = self::create_slug($title);
        
        if (empty($slug)) {
            $slug = $fallback . '-' . time();
        }
        
        return $slug;
    }

    /**
     * Validate slug format
     */
    public static function is_valid_slug(string $slug): bool
    {
        // Slug should only contain lowercase letters, numbers, and hyphens
        // Should not start or end with hyphen
        // Should not contain consecutive hyphens
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    /**
     * Clean existing slug
     */
    public static function clean_slug(string $slug): string
    {
        // Remove any invalid characters
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        
        // Remove multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim hyphens
        $slug = trim($slug, '-');
        
        return empty($slug) ? 'cleaned-slug' : $slug;
    }

    /**
     * Generate SEO-friendly slug with length limit
     */
    public static function create_seo_slug(string $text, int $max_length = 60): string
    {
        $slug = self::create_slug($text);
        
        if (strlen($slug) <= $max_length) {
            return $slug;
        }
        
        // Truncate at word boundary
        $truncated = substr($slug, 0, $max_length);
        $last_dash = strrpos($truncated, '-');
        
        if ($last_dash !== false) {
            $truncated = substr($truncated, 0, $last_dash);
        }
        
        return $truncated;
    }

    /**
     * Create slug from multiple words/phrases
     * 
     * @param array<int, string> $parts
     */
    public static function create_from_parts(array $parts, string $separator = '-'): string
    {
        $slugs = [];
        
        foreach ($parts as $part) {
            $slug = self::create_slug($part);
            if (!empty($slug)) {
                $slugs[] = $slug;
            }
        }
        
        return implode($separator, $slugs);
    }

    /**
     * Extract words from slug
     * 
     * @return array<int, string>
     */
    public static function extract_words(string $slug): array
    {
        return explode('-', $slug);
    }

    /**
     * Check if slug contains specific word
     */
    public static function contains_word(string $slug, string $word): bool
    {
        $words = self::extract_words($slug);
        return in_array(strtolower($word), $words);
    }

    /**
     * Generate slug for file names
     */
    public static function create_filename_slug(string $filename): string
    {
        $path_info = pathinfo($filename);
        $name = $path_info['filename'];
        $extension = $path_info['extension'] ?? '';
        
        $slug = self::create_slug($name);
        
        if (!empty($extension)) {
            $slug .= '.' . strtolower($extension);
        }
        
        return $slug;
    }

    /**
     * Generate category-based slug
     */
    public static function create_category_slug(string $category, string $title): string
    {
        $category_slug = self::create_slug($category);
        $title_slug = self::create_slug($title);
        
        return $category_slug . '-' . $title_slug;
    }

    /**
     * Create dated slug (useful for news articles, blog posts)
     */
    public static function create_dated_slug(string $title, ?\DateTime $date = null): string
    {
        if ($date === null) {
            $date = new \DateTime();
        }
        
        $date_part = $date->format('Y-m-d');
        $title_slug = self::create_slug($title);
        
        return $date_part . '-' . $title_slug;
    }

    /**
     * Generate multilingual slug
     * 
     * @param array<string, string> $translations
     */
    public static function create_multilingual_slug(array $translations, string $default_lang = 'en'): array
    {
        $slugs = [];
        
        foreach ($translations as $lang => $text) {
            $slugs[$lang] = self::create_slug($text);
        }
        
        // Ensure default language has a slug
        if (empty($slugs[$default_lang]) && !empty($translations)) {
            $first_translation = reset($translations);
            $slugs[$default_lang] = self::create_slug($first_translation);
        }
        
        return $slugs;
    }

    /**
     * Create hierarchical slug (for categories with parent-child relationships)
     * 
     * @param array<int, string> $hierarchy
     */
    public static function create_hierarchical_slug(array $hierarchy): string
    {
        $slug_parts = [];
        
        foreach ($hierarchy as $level) {
            $level_slug = self::create_slug($level);
            if (!empty($level_slug)) {
                $slug_parts[] = $level_slug;
            }
        }
        
        return implode('/', $slug_parts);
    }

    /**
     * Generate product slug with SKU integration
     */
    public static function create_product_slug(string $name, ?string $sku = null, ?string $brand = null): string
    {
        $parts = [];
        
        if ($brand) {
            $parts[] = $brand;
        }
        
        $parts[] = $name;
        
        if ($sku) {
            $parts[] = $sku;
        }
        
        return self::create_from_parts($parts);
    }

    /**
     * Create URL path from slug hierarchy
     * 
     * @param array<int, string> $slug_parts
     */
    public static function create_url_path(array $slug_parts): string
    {
        $clean_parts = [];
        
        foreach ($slug_parts as $part) {
            $cleaned = self::clean_slug($part);
            if (!empty($cleaned)) {
                $clean_parts[] = $cleaned;
            }
        }
        
        return '/' . implode('/', $clean_parts);
    }

    /**
     * Generate search-friendly slug
     */
    public static function create_search_slug(string $query): string
    {
        // Remove common search words
        $stop_words = ['de', 'het', 'een', 'en', 'van', 'in', 'op', 'met', 'voor', 'aan', 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $words = explode(' ', strtolower($query));
        $filtered_words = array_filter($words, function($word) use ($stop_words) {
            return !in_array(trim($word), $stop_words) && strlen(trim($word)) > 2;
        });
        
        return self::create_from_parts($filtered_words);
    }

    /**
     * Create versioned slug for revisions
     */
    public static function create_versioned_slug(string $base_slug, int $version): string
    {
        return $base_slug . '-v' . $version;
    }

    /**
     * Generate temporary slug with timestamp
     */
    public static function create_temporary_slug(string $prefix = 'temp'): string
    {
        return $prefix . '-' . time() . '-' . random_int(1000, 9999);
    }

    /**
     * Create slug with language prefix
     */
    public static function create_localized_slug(string $text, string $language): string
    {
        $slug = self::create_slug($text);
        return $language . '-' . $slug;
    }

    /**
     * Generate alternative slugs for A/B testing
     * 
     * @return array<int, string>
     */
    public static function generate_alternative_slugs(string $text, int $count = 3): array
    {
        $base_slug = self::create_slug($text);
        $alternatives = [$base_slug];
        
        $words = self::extract_words($base_slug);
        
        // Create variations by rearranging words
        if (count($words) > 1) {
            // Reverse order
            $alternatives[] = implode('-', array_reverse($words));
            
            // Remove first word
            if (count($words) > 2) {
                $alternatives[] = implode('-', array_slice($words, 1));
            }
            
            // Remove last word
            if (count($words) > 2) {
                $alternatives[] = implode('-', array_slice($words, 0, -1));
            }
        }
        
        // Add numbered variants
        for ($i = 2; $i <= $count && count($alternatives) < $count; $i++) {
            $alternatives[] = $base_slug . '-' . $i;
        }
        
        return array_slice(array_unique($alternatives), 0, $count);
    }

    /**
     * Check slug similarity (Levenshtein distance)
     */
    public static function calculate_similarity(string $slug1, string $slug2): float
    {
        $max_length = max(strlen($slug1), strlen($slug2));
        if ($max_length === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($slug1, $slug2);
        return 1.0 - ($distance / $max_length);
    }

    /**
     * Find similar slugs in array
     * 
     * @param array<int, string> $existing_slugs
     * @return array<int, string>
     */
    public static function find_similar_slugs(string $slug, array $existing_slugs, float $threshold = 0.8): array
    {
        $similar = [];
        
        foreach ($existing_slugs as $existing_slug) {
            $similarity = self::calculate_similarity($slug, $existing_slug);
            if ($similarity >= $threshold) {
                $similar[] = $existing_slug;
            }
        }
        
        return $similar;
    }

    /**
     * Optimize slug for SEO
     */
    public static function optimize_for_seo(string $slug, array $keywords = []): string
    {
        $optimized_slug = self::clean_slug($slug);
        
        // Add important keywords if not present
        foreach ($keywords as $keyword) {
            $keyword_slug = self::create_slug($keyword);
            if (!self::contains_word($optimized_slug, $keyword_slug)) {
                $optimized_slug = $keyword_slug . '-' . $optimized_slug;
            }
        }
        
        // Ensure reasonable length for SEO (Google recommends < 60 characters)
        return self::create_seo_slug($optimized_slug, 50);
    }

    /**
     * Create slug pattern for regex matching
     */
    public static function create_slug_pattern(string $slug): string
    {
        $escaped = preg_quote($slug, '/');
        return '/^' . $escaped . '(-\d+)?$/';
    }

    /**
     * Validate slug against reserved words
     * 
     * @param array<int, string> $reserved_words
     */
    public static function is_reserved_slug(string $slug, array $reserved_words = []): bool
    {
        $default_reserved = [
            'admin', 'api', 'www', 'mail', 'ftp', 'localhost', 'root',
            'test', 'staging', 'dev', 'blog', 'shop', 'store', 'cart',
            'checkout', 'payment', 'order', 'account', 'profile', 'login',
            'register', 'logout', 'search', 'contact', 'about', 'privacy',
            'terms', 'help', 'support', 'home', 'index', 'default'
        ];
        
        $all_reserved = array_merge($default_reserved, $reserved_words);
        
        return in_array(strtolower($slug), array_map('strtolower', $all_reserved));
    }

    /**
     * Generate canonical slug (removes variations and standardizes format)
     */
    public static function canonicalize_slug(string $slug): string
    {
        // Remove version numbers
        $canonical = preg_replace('/-v\d+$/', '', $slug);
        
        // Remove trailing numbers (like -1, -2, etc.)
        $canonical = preg_replace('/-\d+$/', '', $canonical);
        
        // Clean and return
        return self::clean_slug($canonical);
    }
}
