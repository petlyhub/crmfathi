<?php
/**
 * AEOX Cache Manager
 *
 * Handles caching of analysis results to improve performance.
 *
 * @package AEOX_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AEOX_Cache {

    /**
     * Cache group name
     */
    const CACHE_GROUP = 'aeox_seo';

    /**
     * Cache expiration time in seconds (1 hour)
     */
    const CACHE_EXPIRY = HOUR_IN_SECONDS;

    /**
     * Get cache key for post analysis
     *
     * @param int    $post_id Post ID.
     * @param string $type    Analysis type (seo, aeo, geo, etc.).
     * @return string Cache key.
     */
    public function get_analysis_key($post_id, $type = 'all') {
        return "aeox_analysis_{$post_id}_{$type}";
    }

    /**
     * Get cached analysis
     *
     * @param int    $post_id Post ID.
     * @param string $type    Analysis type.
     * @return mixed Cached data or false if not found.
     */
    public function get_analysis($post_id, $type = 'all') {
        $key = $this->get_analysis_key($post_id, $type);
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    /**
     * Set analysis cache
     *
     * @param int    $post_id Post ID.
     * @param array  $data    Analysis data.
     * @param string $type    Analysis type.
     * @return bool Success status.
     */
    public function set_analysis($post_id, $data, $type = 'all') {
        $key = $this->get_analysis_key($post_id, $type);
        return wp_cache_set($key, $data, self::CACHE_GROUP, self::CACHE_EXPIRY);
    }

    /**
     * Delete cached analysis for a post
     *
     * @param int $post_id Post ID.
     * @return bool Success status.
     */
    public function delete_analysis($post_id) {
        $types = ['all', 'seo', 'aeo', 'geo', 'schema', 'entity', 'content'];
        $success = true;

        foreach ($types as $type) {
            $key = $this->get_analysis_key($post_id, $type);
            if (!wp_cache_delete($key, self::CACHE_GROUP)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Clear all AEOX caches
     *
     * @return bool Success status.
     */
    public function flush_all() {
        return wp_cache_flush_group(self::CACHE_GROUP);
    }
}
