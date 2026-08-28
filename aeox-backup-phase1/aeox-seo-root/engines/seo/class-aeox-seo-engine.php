<?php
/**
 * SEO Engine for AEOX SEO
 * Analyzes traditional SEO factors
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AEOX_SEO_Engine {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('aeox_analyze_post', array($this, 'analyze_post'), 10, 2);
    }
    
    /**
     * Analyze a post for SEO factors
     */
    public function analyze_post($post_id, $force = false) {
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }
        
        // Check cache
        $cache = AEOX_Cache::get_instance();
        $cached = $cache->get_analysis($post_id);
        
        if ($cached && !$force) {
            return $cached;
        }
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Get meta data
        $meta_title = get_post_meta($post_id, '_aeox_meta_title', true);
        $meta_description = get_post_meta($post_id, '_aeox_meta_description', true);
        
        // Analyze content
        $analysis = array(
            'title' => !empty($meta_title) ? $meta_title : $title,
            'title_length' => strlen($meta_title ?: $title),
            'meta_description' => $meta_description,
            'meta_description_length' => strlen($meta_description),
            'word_count' => str_word_count(strip_tags($content)),
            'has_h1' => $this->check_heading($content, 'h1'),
            'has_h2' => $this->check_heading($content, 'h2'),
            'has_h3' => $this->check_heading($content, 'h3'),
            'headings' => $this->extract_headings($content),
            'images' => substr_count($content, '<img'),
            'images_with_alt' => preg_match_all('/<img[^>]+alt=["\'][^"\']+["\']/i', $content),
            'internal_links' => $this->count_internal_links($content),
            'external_links' => $this->count_external_links($content),
            'links' => $this->extract_links($content),
            'canonical' => get_permalink($post_id),
            'noindex' => $this->is_noindex($post_id),
            'https' => is_ssl()
        );
        
        // Calculate SEO score
        $scorer = AEOX_Scorer::get_instance();
        $seo_score = $scorer->calculate_seo_score($analysis);
        
        $analysis['seo_score'] = $seo_score;
        
        // Detect issues
        $this->detect_seo_issues($post_id, $analysis);
        
        // Save to database
        $database = AEOX_Database::get_instance();
        $database->save_analysis($post_id, array('seo' => $seo_score));
        
        // Cache results
        $cache->set_analysis($post_id, $analysis, 86400);
        
        return $analysis;
    }
    
    /**
     * Check if heading exists in content
     */
    private function check_heading($content, $level) {
        return preg_match('/<h' . $level . '[^>]*>.*?<\/h' . $level . '>/', $content) > 0;
    }
    
    /**
     * Extract headings from content
     */
    private function extract_headings($content) {
        $headings = array();
        
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $headings[] = array(
                'level' => (int)$match[1],
                'text' => strip_tags($match[2]),
                'html' => $match[0]
            );
        }
        
        return $headings;
    }
    
    /**
     * Count internal links
     */
    private function count_internal_links($content) {
        $site_url = site_url();
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        $count = 0;
        if (isset($matches[1])) {
            foreach ($matches[1] as $url) {
                if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Count external links
     */
    private function count_external_links($content) {
        $site_url = site_url();
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        $count = 0;
        if (isset($matches[1])) {
            foreach ($matches[1] as $url) {
                if (strpos($url, $site_url) !== 0 && strpos($url, '/') !== 0 && strpos($url, '#') !== 0) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Extract all links
     */
    private function extract_links($content) {
        $links = array();
        
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', $content, $matches, PREG_SET_ORDER);
        
        $site_url = site_url();
        
        foreach ($matches as $match) {
            $url = $match[1];
            $text = strip_tags($match[2]);
            $is_internal = (strpos($url, $site_url) === 0 || strpos($url, '/') === 0);
            $is_follow = !preg_match('/rel=["\'][^"\']*nofollow[^"\']*["\']/i', $match[0]);
            
            $links[] = array(
                'url' => $url,
                'text' => $text,
                'type' => $is_internal ? 'internal' : 'external',
                'is_follow' => $is_follow
            );
        }
        
        return $links;
    }
    
    /**
     * Check if page is noindex
     */
    private function is_noindex($post_id) {
        // Check Yoast
        $yoast_meta = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
        if ($yoast_meta == '1') {
            return true;
        }
        
        // Check Rank Math
        $rank_math_meta = get_post_meta($post_id, 'rank_math_robots_noindex', true);
        if ($rank_math_meta) {
            return true;
        }
        
        // Check AEOX meta
        $aeox_meta = get_post_meta($post_id, '_aeox_noindex', true);
        if ($aeox_meta) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Detect SEO issues
     */
    private function detect_seo_issues($post_id, $data) {
        $database = AEOX_Database::get_instance();
        
        // Title issues
        if (empty($data['title'])) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'missing_title',
                'severity' => 'critical',
                'category' => 'seo',
                'detected_value' => '',
                'expected_value' => 'Page title',
                'description' => __('Page has no title', 'aeox-seo'),
                'how_to_fix' => __('Add a descriptive title to your page', 'aeox-seo'),
                'is_resolved' => 0
            ));
        } elseif ($data['title_length'] < 30 || $data['title_length'] > 60) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'title_length',
                'severity' => 'warning',
                'category' => 'seo',
                'detected_value' => $data['title_length'],
                'expected_value' => '30-60 characters',
                'description' => __('Title length is not optimal', 'aeox-seo'),
                'how_to_fix' => __('Adjust title length to be between 30-60 characters', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // Meta description issues
        if (empty($data['meta_description'])) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'missing_meta_description',
                'severity' => 'critical',
                'category' => 'seo',
                'detected_value' => '',
                'expected_value' => 'Meta description',
                'description' => __('Page has no meta description', 'aeox-seo'),
                'how_to_fix' => __('Add a compelling meta description (120-160 characters)', 'aeox-seo'),
                'is_resolved' => 0
            ));
        } elseif ($data['meta_description_length'] < 120 || $data['meta_description_length'] > 160) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'meta_description_length',
                'severity' => 'warning',
                'category' => 'seo',
                'detected_value' => $data['meta_description_length'],
                'expected_value' => '120-160 characters',
                'description' => __('Meta description length is not optimal', 'aeox-seo'),
                'how_to_fix' => __('Adjust meta description to be between 120-160 characters', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // H1 issues
        if (!$data['has_h1']) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'missing_h1',
                'severity' => 'critical',
                'category' => 'seo',
                'detected_value' => 'No H1',
                'expected_value' => 'One H1 heading',
                'description' => __('Page has no H1 heading', 'aeox-seo'),
                'how_to_fix' => __('Add an H1 heading that describes the page content', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // Word count issues
        if ($data['word_count'] < 300) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'low_word_count',
                'severity' => 'warning',
                'category' => 'seo',
                'detected_value' => $data['word_count'],
                'expected_value' => '300+ words',
                'description' => __('Content is too short', 'aeox-seo'),
                'how_to_fix' => __('Expand your content to at least 300 words', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // Image ALT issues
        if ($data['images'] > 0 && $data['images_with_alt'] < $data['images']) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'missing_alt_text',
                'severity' => 'warning',
                'category' => 'seo',
                'detected_value' => $data['images'] - $data['images_with_alt'],
                'expected_value' => 'All images should have alt text',
                'description' => __('Some images are missing alt text', 'aeox-seo'),
                'how_to_fix' => __('Add descriptive alt text to all images', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // Internal links issues
        if ($data['internal_links'] == 0) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'no_internal_links',
                'severity' => 'notice',
                'category' => 'seo',
                'detected_value' => '0',
                'expected_value' => 'At least 1 internal link',
                'description' => __('Page has no internal links', 'aeox-seo'),
                'how_to_fix' => __('Add links to other relevant pages on your site', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
    }
    
    /**
     * Get focus keyword
     */
    public function get_focus_keyword($post_id) {
        return get_post_meta($post_id, '_aeox_focus_keyword', true);
    }
    
    /**
     * Update focus keyword
     */
    public function update_focus_keyword($post_id, $keyword) {
        update_post_meta($post_id, '_aeox_focus_keyword', sanitize_text_field($keyword));
    }
}
