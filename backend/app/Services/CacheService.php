<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // TTLs (seconds)
    const TTL_LAYOUT = 900;   // 15 min — navigation, tags, footer
    const TTL_HOME = 600;     // 10 min — home page
    const TTL_ARTICLE = 900;  // 15 min — article page
    const TTL_LIST = 300;     //  5 min — paginated lists
    const TTL_SIDEBAR = 1800; // 30 min — popular, archive

    // Cache tags
    const TAG_LAYOUT     = 'layout';
    const TAG_HOME       = 'home';
    const TAG_ARTICLES   = 'articles';
    const TAG_CATEGORIES = 'categories';

    /**
     * Flush cache on article write (create / update).
     */
    public static function flushOnArticleWrite(): void
    {
        Cache::tags([self::TAG_ARTICLES, self::TAG_HOME, self::TAG_LAYOUT])->flush();
    }

    /**
     * Flush cache on category / subcategory write.
     */
    public static function flushOnCategoryWrite(): void
    {
        Cache::tags([
            self::TAG_LAYOUT,
            self::TAG_HOME,
            self::TAG_CATEGORIES,
            self::TAG_ARTICLES,
        ])->flush();
    }

    /**
     * Flush cache on tag write.
     */
    public static function flushOnTagWrite(): void
    {
        Cache::tags([self::TAG_LAYOUT, self::TAG_ARTICLES])->flush();
    }
}
