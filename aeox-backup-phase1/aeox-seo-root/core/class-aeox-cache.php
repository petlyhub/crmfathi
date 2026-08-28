<?php
/**
 * Cache Handler for AEOX SEO
 * Manages object caching and transients
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AEOX_Cache {
    
    private static $instance = null;
    private $cache_group = 'aeox_seo';
    private $cache_prefix = 'aeox_';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached value
     */
    public function get($key, $post_id = null) {
        $cache_key = $this->get_cache_key($key, $post_id);
        
        $value = wp_cache_get($cache_key, $this->cache_group);
        
        if ($value === false) {
            // Try transient
            $value = get_transient($cache_key);
        }
        
        return $value;
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $expiration = 3600, $post_id = null) {
        $cache_key = $this->get_cache_key($key, $post_id);
        
        wp_cache_set($cache_key, $value, $this->cache_group, $expiration);
        set_transient($cache_key, $value, $expiration);
    }
    
    /**
     * Delete cache
     */
    public function delete($key, $post_id = null) {
        $cache_key = $this->get_cache_key($key, $post_id);
        
        wp_cache_delete($cache_key, $this->cache_group);
        delete_transient($cache_key);
    }
    
    /**
     * Clear all AEOX cache
     */
    public function clear_all() {
        wp_cache_flush_group($this->cache_group);
        
        // Delete transients (WordPress doesn't have a bulk delete for transients by group)
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $this->cache_prefix . '%',
            '_transient_timeout_' . $this->cache_prefix . '%'
        ));
    }
    
    /**
     * Get cache key
     */
    private function get_cache_key($key, $post_id = null) {
        if ($post_id) {
            return $this->cache_prefix . $key . '_' . $post_id;
        }
        return $this->cache_prefix . $key;
    }
    
    /**
     * Get analysis cache
     */
    public function get_analysis($post_id) {
        return $this->get('analysis', $post_id);
    }
    
    /**
     * Set analysis cache
     */
    public function set_analysis($post_id, $data, $expiration = 86400) {
        $this->set('analysis', $data, $expiration, $post_id);
    }
    
    /**
     * Get schema cache
     */
    public function get_schema($post_id) {
        return $this->get('schema', $post_id);
    }
    
    /**
     * Set schema cache
     */
    public function set_schema($post_id, $data, $expiration = 604800) {
        $this->set('schema', $data, $expiration, $post_id);
    }
    
    /**
     * Get entities cache
     */
    public function get_entities($post_id) {
        return $this->get('entities', $post_id);
    }
    
    /**
     * Set entities cache
     */
    public function set_entities($post_id, $data, $expiration = 604800) {
        $this->set('entities', $data, $expiration, $post_id);
    }
    
    /**
     * Invalidate post cache on update
     */
    public function invalidate_post($post_id) {
        $this->delete('analysis', $post_id);
        $this->delete('schema', $post_id);
        $this->delete('entities', $post_id);
        $this->delete('seo', $post_id);
        $this->delete('aeo', $post_id);
        $this->delete('geo', $post_id);
    }
}
