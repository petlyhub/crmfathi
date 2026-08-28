<?php
/**
 * AEOX SEO Engine
 *
 * Complete SEO analysis engine for titles, meta descriptions, headings, links, images, and more.
 *
 * @package AEOX_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AEOX_SEO_Engine {
    
    private static $instance = null;
    
    /**
     * Analysis cache
     */
    private $cache = array();
    
    /**
     * Issue categories
     */
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_NOTICE = 'notice';
    const SEVERITY_INFO = 'info';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Private constructor for singleton
    }
    
    /**
     * Analyze post for SEO factors
     *
     * @param int $post_id Post ID.
     * @return array Analysis results with scores, issues, and recommendations.
     */
    public function analyze($post_id) {
        $post_id = absint($post_id);
        
        if (empty($post_id)) {
            return $this->get_empty_result();
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return $this->get_empty_result();
        }
        
        // Get post content and metadata
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Get SEO meta if set
        $meta_title = get_post_meta($post_id, '_aeox_meta_title', true);
        $meta_description = get_post_meta($post_id, '_aeox_meta_description', true);
        $focus_keyword = get_post_meta($post_id, '_aeox_focus_keyword', true);
        
        // Parse content
        $parsed = $this->parse_content($content);
        
        // Perform analysis
        $analysis = array(
            'title' => $this->analyze_title($meta_title ? $meta_title : $title, $focus_keyword),
            'meta_description' => $this->analyze_meta_description($meta_description, $focus_keyword),
            'headings' => $this->analyze_headings($content),
            'links' => $this->analyze_links($content, $post_id),
            'images' => $this->analyze_images($content, $post_id),
            'content' => $this->analyze_content($content, $focus_keyword),
            'technical' => $this->analyze_technical($post_id, $post),
            'readability' => $this->analyze_readability($content),
        );
        
        // Calculate scores
        $scores = $this->calculate_scores($analysis);
        
        // Generate issues and opportunities
        $issues = $this->generate_issues($analysis, $scores);
        
        // Save to database
        $this->save_analysis($post_id, $scores, $analysis);
        
        return array(
            'post_id' => $post_id,
            'scores' => $scores,
            'analysis' => $analysis,
            'issues' => $issues['issues'],
            'opportunities' => $issues['opportunities'],
            'recommendations' => $this->generate_recommendations($issues),
            'analyzed_at' => current_time('mysql'),
        );
    }
    
    /**
     * Parse post content to extract elements
     */
    private function parse_content($content) {
        // Strip shortcodes
        $content = strip_shortcodes($content);
        
        // Remove HTML comments
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        
        return $content;
    }
    
    /**
     * Analyze title
     */
    private function analyze_title($title, $focus_keyword = '') {
        $length = strlen($title);
        $word_count = str_word_count($title);
        
        $result = array(
            'value' => $title,
            'length' => $length,
            'word_count' => $word_count,
            'has_keyword' => false,
            'keyword_position' => -1,
            'is_optimal_length' => false,
            'has_numbers' => preg_match('/\d+/', $title) > 0,
            'has_power_words' => false,
            'sentiment' => 'neutral',
        );
        
        // Check keyword presence
        if (!empty($focus_keyword)) {
            $result['has_keyword'] = stripos($title, $focus_keyword) !== false;
            if ($result['has_keyword']) {
                $result['keyword_position'] = stripos($title, $focus_keyword);
            }
        }
        
        // Optimal length: 30-60 characters (Google displays ~50-60)
        $result['is_optimal_length'] = ($length >= 30 && $length <= 60);
        
        // Check for power words
        $power_words = array('ultimate', 'essential', 'proven', 'complete', 'best', 'top', 'free', 'new', 'easy', 'fast');
        foreach ($power_words as $word) {
            if (stripos($title, $word) !== false) {
                $result['has_power_words'] = true;
                break;
            }
        }
        
        // Simple sentiment check
        $positive_words = array('best', 'great', 'excellent', 'amazing', 'awesome', 'perfect', 'easy', 'fast', 'free');
        $negative_words = array('worst', 'bad', 'terrible', 'avoid', 'never', 'difficult', 'slow');
        
        $pos_count = 0;
        $neg_count = 0;
        
        foreach ($positive_words as $word) {
            if (stripos($title, $word) !== false) {
                $pos_count++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (stripos($title, $word) !== false) {
                $neg_count++;
            }
        }
        
        if ($pos_count > $neg_count) {
            $result['sentiment'] = 'positive';
        } elseif ($neg_count > $pos_count) {
            $result['sentiment'] = 'negative';
        }
        
        return $result;
    }
    
    /**
     * Analyze meta description
     */
    private function analyze_meta_description($description, $focus_keyword = '') {
        if (empty($description)) {
            return array(
                'value' => '',
                'length' => 0,
                'is_optimal_length' => false,
                'has_keyword' => false,
                'has_cta' => false,
                'is_unique' => true, // Can't check without DB query
                'recommendation' => __('Add a meta description (120-160 characters)', 'aeox-seo'),
            );
        }
        
        $length = strlen($description);
        
        $result = array(
            'value' => $description,
            'length' => $length,
            'is_optimal_length' => ($length >= 120 && $length <= 160),
            'has_keyword' => !empty($focus_keyword) && stripos($description, $focus_keyword) !== false,
            'has_cta' => $this->has_call_to_action($description),
            'sentence_count' => preg_match_all('/[.!?]+/', $description),
        );
        
        // Check for duplicate (simplified - would need full DB query in production)
        $result['is_unique'] = true;
        
        if ($length < 120) {
            $result['recommendation'] = sprintf(
                __('Meta description is too short (%d chars). Aim for 120-160 characters.', 'aeox-seo'),
                $length
            );
        } elseif ($length > 160) {
            $result['recommendation'] = sprintf(
                __('Meta description is too long (%d chars). Aim for 120-160 characters.', 'aeox-seo'),
                $length
            );
        } else {
            $result['recommendation'] = __('Meta description length is optimal.', 'aeox-seo');
        }
        
        return $result;
    }
    
    /**
     * Check if text contains a call-to-action
     */
    private function has_call_to_action($text) {
        $cta_patterns = array(
            '/learn more/i',
            '/read more/i',
            '/click here/i',
            '/discover/i',
            '/find out/i',
            '/get started/i',
            '/try now/i',
            '/sign up/i',
            '/subscribe/i',
            '/download/i',
            '/buy now/i',
            '/shop now/i',
            '/explore/i',
            '/see how/i',
            '/start/i',
        );
        
        foreach ($cta_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Analyze headings structure
     */
    private function analyze_headings($content) {
        $result = array(
            'h1_count' => 0,
            'h1_texts' => array(),
            'h2_count' => 0,
            'h2_texts' => array(),
            'h3_count' => 0,
            'h3_texts' => array(),
            'h4_count' => 0,
            'h5_count' => 0,
            'h6_count' => 0,
            'has_proper_hierarchy' => true,
            'heading_keywords' => array(),
        );
        
        // Extract headings using regex
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/si', $content, $matches, PREG_SET_ORDER);
        
        $last_level = 0;
        foreach ($matches as $match) {
            $level = (int)$match[1];
            $text = wp_strip_all_tags($match[2]);
            
            if (!empty($text)) {
                $result["h{$level}_count"]++;
                $result["h{$level}_texts"][] = $text;
                
                // Check hierarchy (simplified)
                if ($level > $last_level + 1 && $last_level > 0) {
                    $result['has_proper_hierarchy'] = false;
                }
                $last_level = $level;
            }
        }
        
        // If no headings in content, check if post title serves as H1
        if ($result['h1_count'] === 0) {
            global $post;
            if ($post && is_singular()) {
                // Post title typically renders as H1 in themes
                $result['h1_from_title'] = true;
            }
        }
        
        return $result;
    }
    
    /**
     * Analyze links in content
     */
    private function analyze_links($content, $post_id) {
        $result = array(
            'internal_links' => 0,
            'external_links' => 0,
            'internal_urls' => array(),
            'external_urls' => array(),
            'follow_links' => 0,
            'nofollow_links' => 0,
            'broken_links' => 0,
            'link_text_analysis' => array(),
        );
        
        $site_url = get_site_url();
        $home_url = home_url();
        
        preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/si', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $url = $match[2];
            $text = wp_strip_all_tags($match[3]);
            $is_nofollow = strpos($match[0], 'rel="nofollow"') !== false || strpos($match[0], "rel='nofollow'") !== false;
            
            // Determine if internal or external
            $is_internal = (strpos($url, $site_url) === 0) || 
                          (strpos($url, $home_url) === 0) || 
                          (strpos($url, '/') === 0 && strpos($url, '//') !== 0) ||
                          (strpos($url, '#') === 0);
            
            if ($is_internal) {
                $result['internal_links']++;
                $result['internal_urls'][] = array(
                    'url' => $url,
                    'text' => $text,
                    'nofollow' => $is_nofollow,
                );
            } else {
                $result['external_links']++;
                $result['external_urls'][] = array(
                    'url' => $url,
                    'text' => $text,
                    'nofollow' => $is_nofollow,
                );
            }
            
            if ($is_nofollow) {
                $result['nofollow_links']++;
            } else {
                $result['follow_links']++;
            }
            
            // Analyze anchor text
            if (!empty($text)) {
                $result['link_text_analysis'][] = array(
                    'text' => $text,
                    'length' => strlen($text),
                    'is_generic' => in_array(strtolower($text), array('click here', 'read more', 'here', 'link', 'more')),
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Analyze images in content
     */
    private function analyze_images($content, $post_id) {
        $result = array(
            'total_images' => 0,
            'images_with_alt' => 0,
            'images_without_alt' => 0,
            'images_with_title' => 0,
            'images_without_title' => 0,
            'image_details' => array(),
            'alt_text_quality' => array(),
        );
        
        preg_match_all('/<img\s+([^>]*?)\s*\/?>/si', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attrs = $match[1];
            $result['total_images']++;
            
            $has_alt = preg_match('/alt=(["\'])(.*?)\1/', $attrs, $alt_match);
            $has_title = preg_match('/title=(["\'])(.*?)\1/', $attrs, $title_match);
            
            $image_data = array(
                'has_alt' => $has_alt > 0,
                'alt_text' => $has_alt > 0 ? $alt_match[2] : '',
                'has_title' => $has_title > 0,
                'title_text' => $has_title > 0 ? $title_match[2] : '',
            );
            
            if ($has_alt > 0 && !empty($alt_match[2])) {
                $result['images_with_alt']++;
                $image_data['alt_length'] = strlen($alt_match[2]);
                $image_data['alt_is_descriptive'] = strlen($alt_match[2]) > 5;
            } else {
                $result['images_without_alt']++;
            }
            
            if ($has_title > 0 && !empty($title_match[2])) {
                $result['images_with_title']++;
            } else {
                $result['images_without_title']++;
            }
            
            $result['image_details'][] = $image_data;
        }
        
        // Also check featured image
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $alt_text = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
            $result['has_featured_image'] = true;
            $result['featured_image_alt'] = $alt_text;
            $result['featured_image_alt_set'] = !empty($alt_text);
            
            if (!empty($alt_text)) {
                $result['images_with_alt']++;
            } else {
                $result['images_without_alt']++;
            }
            $result['total_images']++;
        } else {
            $result['has_featured_image'] = false;
        }
        
        return $result;
    }
    
    /**
     * Analyze content quality
     */
    private function analyze_content($content, $focus_keyword = '') {
        $plain_text = wp_strip_all_tags($content);
        $word_count = str_word_count($plain_text);
        $char_count = strlen($plain_text);
        
        $result = array(
            'word_count' => $word_count,
            'char_count' => $char_count,
            'paragraph_count' => substr_count($content, '</p>'),
            'has_shortcodes' => has_shortcode($content),
            'keyword_density' => 0,
            'keyword_in_first_paragraph' => false,
            'keyword_occurrences' => 0,
            'content_type' => 'mixed',
            'has_list' => false,
            'has_table' => false,
            'has_video' => false,
            'has_audio' => false,
        );
        
        // Keyword density
        if (!empty($focus_keyword) && $word_count > 0) {
            $keyword_lower = strtolower($focus_keyword);
            $content_lower = strtolower($plain_text);
            $occurrences = substr_count($content_lower, $keyword_lower);
            $result['keyword_occurrences'] = $occurrences;
            $result['keyword_density'] = round(($occurrences / $word_count) * 100, 2);
            
            // Check first paragraph
            preg_match('/<p>(.*?)<\/p>/s', $content, $first_para);
            if (!empty($first_para[1])) {
                $result['keyword_in_first_paragraph'] = stripos(wp_strip_all_tags($first_para[1]), $keyword_lower) !== false;
            }
        }
        
        // Content type detection
        if (strpos($content, '<ul') !== false || strpos($content, '<ol') !== false) {
            $result['has_list'] = true;
        }
        
        if (strpos($content, '<table') !== false) {
            $result['has_table'] = true;
        }
        
        if (strpos($content, '[video') !== false || strpos($content, '<video') !== false || 
            strpos($content, 'youtube') !== false || strpos($content, 'vimeo') !== false) {
            $result['has_video'] = true;
        }
        
        if (strpos($content, '[audio') !== false || strpos($content, '<audio') !== false) {
            $result['has_audio'] = true;
        }
        
        // Determine primary content type
        if ($result['has_video']) {
            $result['content_type'] = 'multimedia';
        } elseif ($result['has_table']) {
            $result['content_type'] = 'data';
        } elseif ($result['has_list'] && $result['paragraph_count'] < 3) {
            $result['content_type'] = 'list';
        }
        
        return $result;
    }
    
    /**
     * Analyze technical SEO factors
     */
    private function analyze_technical($post_id, $post) {
        $result = array(
            'post_status' => $post->post_status,
            'post_type' => $post->post_type,
            'is_public' => false,
            'is_indexable' => true,
            'canonical_url' => get_permalink($post_id),
            'has_ssl' => is_ssl(),
            'url_length' => strlen(get_permalink($post_id)),
            'url_has_keywords' => false,
            'is_mobile_friendly' => true, // Assumed - theme dependent
            'page_speed_ready' => true, // Assumed
        );
        
        // Check if post type is public
        $post_type_obj = get_post_type_object($post->post_type);
        if ($post_type_obj && $post_type_obj->public) {
            $result['is_public'] = true;
        }
        
        // Check robots meta
        $noindex = get_post_meta($post_id, '_aeox_noindex', true);
        if ($noindex) {
            $result['is_indexable'] = false;
        }
        
        // Check URL for keywords (if focus keyword exists)
        $focus_keyword = get_post_meta($post_id, '_aeox_focus_keyword', true);
        if (!empty($focus_keyword)) {
            $url = get_permalink($post_id);
            $slug = basename($url);
            $result['url_has_keywords'] = stripos($slug, sanitize_title($focus_keyword)) !== false;
        }
        
        // Check for trailing slash
        $result['has_trailing_slash'] = substr($result['canonical_url'], -1) === '/';
        
        return $result;
    }
    
    /**
     * Analyze readability
     */
    private function analyze_readability($content) {
        $plain_text = wp_strip_all_tags($content);
        
        $result = array(
            'word_count' => str_word_count($plain_text),
            'sentence_count' => preg_match_all('/[.!?]+/', $plain_text),
            'paragraph_count' => substr_count($content, '</p>'),
            'avg_sentence_length' => 0,
            'avg_paragraph_length' => 0,
            'flesch_reading_ease' => 0,
            'grade_level' => 'unknown',
            'has_transition_words' => false,
            'passive_voice_percentage' => 0,
        );
        
        if ($result['sentence_count'] > 0) {
            $result['avg_sentence_length'] = round($result['word_count'] / $result['sentence_count'], 1);
        }
        
        if ($result['paragraph_count'] > 0) {
            $result['avg_paragraph_length'] = round($result['word_count'] / $result['paragraph_count'], 1);
        }
        
        // Simplified Flesch Reading Ease calculation
        // FRE = 206.835 - 1.015(words/sentences) - 84.6(syllables/words)
        if ($result['sentence_count'] > 0 && $result['word_count'] > 0) {
            $syllable_count = $this->count_syllables($plain_text);
            $words_per_sentence = $result['word_count'] / $result['sentence_count'];
            $syllables_per_word = $syllable_count / $result['word_count'];
            
            $fre = 206.835 - (1.015 * $words_per_sentence) - (84.6 * $syllables_per_word);
            $result['flesch_reading_ease'] = round(max(0, min(100, $fre)), 1);
            
            // Grade level estimation
            if ($result['flesch_reading_ease'] >= 90) {
                $result['grade_level'] = '5th grade (very easy)';
            } elseif ($result['flesch_reading_ease'] >= 80) {
                $result['grade_level'] = '6th grade (easy)';
            } elseif ($result['flesch_reading_ease'] >= 70) {
                $result['grade_level'] = '7th grade (fairly easy)';
            } elseif ($result['flesch_reading_ease'] >= 60) {
                $result['grade_level'] = '8th-9th grade (standard)';
            } elseif ($result['flesch_reading_ease'] >= 50) {
                $result['grade_level'] = '10th-12th grade (fairly difficult)';
            } else {
                $result['grade_level'] = 'College (difficult)';
            }
        }
        
        // Check for transition words
        $transition_words = array('however', 'therefore', 'moreover', 'furthermore', 'consequently', 
                                  'additionally', 'meanwhile', 'nevertheless', 'subsequently', 
                                  'accordingly', 'thus', 'hence', 'then', 'first', 'second', 
                                  'finally', 'in conclusion', 'in summary', 'for example', 
                                  'such as', 'in addition', 'on the other hand');
        
        $content_lower = strtolower($plain_text);
        $transition_count = 0;
        foreach ($transition_words as $word) {
            if (strpos($content_lower, $word) !== false) {
                $transition_count++;
            }
        }
        $result['has_transition_words'] = $transition_count >= 3;
        $result['transition_word_count'] = $transition_count;
        
        // Passive voice estimation (simplified)
        $passive_patterns = array('/was\s+\w+ed\b/i', '/were\s+\w+ed\b/i', '/is\s+\w+ed\b/i', 
                                  '/are\s+\w+ed\b/i', '/been\s+\w+ed\b/i', '/be\s+\w+ed\b/i');
        $passive_count = 0;
        foreach ($passive_patterns as $pattern) {
            $passive_count += preg_match_all($pattern, $content);
        }
        
        if ($result['sentence_count'] > 0) {
            $result['passive_voice_percentage'] = round(($passive_count / $result['sentence_count']) * 100, 1);
        }
        
        return $result;
    }
    
    /**
     * Count syllables in text (simplified algorithm)
     */
    private function count_syllables($text) {
        $words = preg_split('/\s+/', strtolower($text));
        $total = 0;
        
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);
            if (strlen($word) <= 3) {
                $total += 1;
                continue;
            }
            
            // Count vowel groups
            $vowel_groups = preg_match_all('/[aeiouy]+/', $word, $matches);
            
            // Adjust for silent e
            if (substr($word, -1) === 'e') {
                $vowel_groups--;
            }
            
            $total += max(1, $vowel_groups);
        }
        
        return $total;
    }
    
    /**
     * Calculate SEO scores from analysis
     */
    private function calculate_scores($analysis) {
        $scorer = AEOX_Scorer::get_instance();
        
        // Prepare data for scorer
        $data = array(
            'title' => $analysis['title']['value'],
            'title_length' => $analysis['title']['length'],
            'meta_description' => $analysis['meta_description']['value'],
            'meta_description_length' => $analysis['meta_description']['length'],
            'has_h1' => $analysis['headings']['h1_count'] > 0 || isset($analysis['headings']['h1_from_title']),
            'has_h2' => $analysis['headings']['h2_count'] > 0,
            'internal_links' => $analysis['links']['internal_links'],
            'external_links' => $analysis['links']['external_links'],
            'images' => $analysis['images']['total_images'],
            'images_with_alt' => $analysis['images']['images_with_alt'],
            'word_count' => $analysis['content']['word_count'],
            'canonical' => $analysis['technical']['canonical_url'],
            'noindex' => !$analysis['technical']['is_indexable'],
        );
        
        $seo_score = $scorer->calculate_seo_score($data);
        
        // Content score based on depth and quality
        $content_score = $this->calculate_content_score($analysis);
        
        // Technical score
        $technical_score = $this->calculate_technical_score($analysis);
        
        // Overall SEO score (weighted average)
        $overall = round(($seo_score * 0.6) + ($content_score * 0.25) + ($technical_score * 0.15));
        
        return array(
            'seo' => $seo_score,
            'content' => $content_score,
            'technical' => $technical_score,
            'overall' => min(100, $overall),
        );
    }
    
    /**
     * Calculate content quality score
     */
    private function calculate_content_score($analysis) {
        $score = 0;
        $max = 0;
        
        $content = $analysis['content'];
        $readability = $analysis['readability'];
        
        // Word count (max 25 points)
        $max += 25;
        if ($content['word_count'] >= 300) $score += 10;
        if ($content['word_count'] >= 500) $score += 15;
        if ($content['word_count'] >= 1000) $score += 20;
        if ($content['word_count'] >= 2000) $score += 25;
        
        // Paragraphs (max 15 points)
        $max += 15;
        if ($content['paragraph_count'] >= 3) $score += 5;
        if ($content['paragraph_count'] >= 5) $score += 10;
        if ($content['paragraph_count'] >= 10) $score += 15;
        
        // Headings (max 20 points)
        $headings = $analysis['headings'];
        $max += 20;
        if ($headings['h1_count'] > 0 || isset($headings['h1_from_title'])) $score += 5;
        if ($headings['h2_count'] > 0) $score += 5;
        if ($headings['h2_count'] >= 2) $score += 5;
        if ($headings['has_proper_hierarchy']) $score += 5;
        
        // Media (max 20 points)
        $max += 20;
        if ($analysis['images']['total_images'] > 0) $score += 5;
        if ($analysis['images']['images_with_alt'] == $analysis['images']['total_images']) $score += 5;
        if ($content['has_video'] || $content['has_audio']) $score += 5;
        if ($content['has_list'] || $content['has_table']) $score += 5;
        
        // Readability (max 20 points)
        $max += 20;
        if ($readability['flesch_reading_ease'] >= 60) $score += 10;
        if ($readability['has_transition_words']) $score += 5;
        if ($readability['passive_voice_percentage'] <= 10) $score += 5;
        
        return $max > 0 ? round(($score / $max) * 100) : 0;
    }
    
    /**
     * Calculate technical SEO score
     */
    private function calculate_technical_score($analysis) {
        $score = 0;
        $max = 0;
        
        $technical = $analysis['technical'];
        
        // Is public (max 20 points)
        $max += 20;
        if ($technical['is_public']) $score += 20;
        
        // Is indexable (max 20 points)
        $max += 20;
        if ($technical['is_indexable']) $score += 20;
        
        // Has SSL (max 15 points)
        $max += 15;
        if ($technical['has_ssl']) $score += 15;
        
        // URL optimization (max 15 points)
        $max += 15;
        if ($technical['url_length'] <= 75) $score += 5;
        if ($technical['url_has_keywords']) $score += 10;
        
        // Canonical URL (max 15 points)
        $max += 15;
        if (!empty($technical['canonical_url'])) $score += 15;
        
        // Mobile friendly (max 15 points)
        $max += 15;
        if ($technical['is_mobile_friendly']) $score += 15;
        
        return $max > 0 ? round(($score / $max) * 100) : 0;
    }
    
    /**
     * Generate issues from analysis
     */
    private function generate_issues($analysis, $scores) {
        $issues = array();
        $opportunities = array();
        
        $issue_id = 0;
        
        // Title issues
        $title = $analysis['title'];
        if (empty($title['value'])) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'title_missing',
                self::SEVERITY_CRITICAL,
                'seo',
                __('No title found', 'aeox-seo'),
                '',
                __('Add a compelling title (30-60 characters)', 'aeox-seo'),
                __('A title is essential for SEO and click-through rates.', 'aeox-seo')
            );
        } elseif (!$title['is_optimal_length']) {
            $severity = $title['length'] < 30 ? self::SEVERITY_WARNING : self::SEVERITY_NOTICE;
            $issues[] = $this->create_issue(
                ++$issue_id,
                'title_length',
                $severity,
                'seo',
                sprintf(__('Title length is %d characters', 'aeox-seo'), $title['length']),
                __('30-60 characters', 'aeox-seo'),
                $title['length'] < 30 
                    ? __('Make your title longer and more descriptive.', 'aeox-seo')
                    : __('Shorten your title to improve display in search results.', 'aeox-seo'),
                __('Optimal title length ensures full display in search results.', 'aeox-seo')
            );
        }
        
        if (!empty($title['value']) && !$title['has_keyword'] && !empty($analysis['content']['keyword_occurrences'])) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'title_keyword_missing',
                self::SEVERITY_NOTICE,
                'seo',
                __('Focus keyword not in title', 'aeox-seo'),
                __('Include focus keyword', 'aeox-seo'),
                __('Add your focus keyword to the title for better relevance.', 'aeox-seo'),
                __('Keywords in titles improve search relevance.', 'aeox-seo')
            );
        }
        
        // Meta description issues
        $meta = $analysis['meta_description'];
        if (empty($meta['value'])) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'meta_description_missing',
                self::SEVERITY_WARNING,
                'seo',
                __('No meta description found', 'aeox-seo'),
                __('120-160 characters', 'aeox-seo'),
                __('Add a compelling meta description to improve click-through rates.', 'aeox-seo'),
                __('Meta descriptions influence click-through rates from search results.', 'aeox-seo')
            );
        } elseif (!$meta['is_optimal_length']) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'meta_description_length',
                self::SEVERITY_NOTICE,
                'seo',
                sprintf(__('Meta description is %d characters', 'aeox-seo'), $meta['length']),
                __('120-160 characters', 'aeox-seo'),
                $meta['length'] < 120 
                    ? __('Expand your meta description to provide more context.', 'aeox-seo')
                    : __('Shorten your meta description to avoid truncation.', 'aeox-seo'),
                __('Optimal length ensures your full description displays in search results.', 'aeox-seo')
            );
        }
        
        // Headings issues
        $headings = $analysis['headings'];
        if ($headings['h1_count'] === 0 && !isset($headings['h1_from_title'])) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'h1_missing',
                self::SEVERITY_CRITICAL,
                'content',
                __('No H1 heading found', 'aeox-seo'),
                __('One H1 heading', 'aeox-seo'),
                __('Add an H1 heading to structure your content.', 'aeox-seo'),
                __('H1 headings help search engines understand your main topic.', 'aeox-seo')
            );
        }
        
        if ($headings['h1_count'] > 1) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'h1_multiple',
                self::SEVERITY_WARNING,
                'content',
                sprintf(__('Multiple H1 headings found (%d)', 'aeox-seo'), $headings['h1_count']),
                __('One H1 heading', 'aeox-seo'),
                __('Use only one H1 heading per page.', 'aeox-seo'),
                __('Multiple H1 tags can confuse search engines about your main topic.', 'aeox-seo')
            );
        }
        
        if (!$headings['has_proper_hierarchy']) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'heading_hierarchy',
                self::SEVERITY_NOTICE,
                'content',
                __('Heading hierarchy issues detected', 'aeox-seo'),
                __('Proper H1 → H2 → H3 structure', 'aeox-seo'),
                __('Ensure headings follow a logical order without skipping levels.', 'aeox-seo'),
                __('Proper heading hierarchy improves content structure and accessibility.', 'aeox-seo')
            );
        }
        
        // Links issues
        $links = $analysis['links'];
        if ($links['internal_links'] === 0) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'no_internal_links',
                self::SEVERITY_WARNING,
                'seo',
                __('No internal links found', 'aeox-seo'),
                __('At least 2-3 internal links', 'aeox-seo'),
                __('Add relevant internal links to other pages on your site.', 'aeox-seo'),
                __('Internal links help distribute page authority and improve navigation.', 'aeox-seo')
            );
        }
        
        if ($links['external_links'] === 0) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'no_external_links',
                self::SEVERITY_NOTICE,
                'content',
                __('No external links found', 'aeox-seo'),
                __('Link to authoritative sources', 'aeox-seo'),
                __('Consider adding links to high-quality external resources.', 'aeox-seo'),
                __('External links to authoritative sources can improve credibility.', 'aeox-seo')
            );
        }
        
        // Check for generic link text
        foreach ($links['link_text_analysis'] as $link) {
            if ($link['is_generic']) {
                $opportunities[] = $this->create_issue(
                    ++$issue_id,
                    'generic_link_text',
                    self::SEVERITY_NOTICE,
                    'seo',
                    sprintf(__('Generic link text: "%s"', 'aeox-seo'), $link['text']),
                    __('Descriptive anchor text', 'aeox-seo'),
                    __('Use descriptive anchor text instead of generic phrases.', 'aeox-seo'),
                    __('Descriptive anchor text improves SEO and accessibility.', 'aeox-seo')
                );
                break; // Only report once
            }
        }
        
        // Images issues
        $images = $analysis['images'];
        if ($images['total_images'] > 0 && $images['images_without_alt'] > 0) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'images_missing_alt',
                self::SEVERITY_WARNING,
                'seo',
                sprintf(__('%d images missing ALT text', 'aeox-seo'), $images['images_without_alt']),
                __('All images should have ALT text', 'aeox-seo'),
                __('Add descriptive ALT text to all images.', 'aeox-seo'),
                __('ALT text improves accessibility and helps images rank in search.', 'aeox-seo')
            );
        }
        
        if (!$images['has_featured_image']) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'no_featured_image',
                self::SEVERITY_NOTICE,
                'content',
                __('No featured image set', 'aeox-seo'),
                __('Set a featured image', 'aeox-seo'),
                __('Add a featured image to improve social sharing and visual appeal.', 'aeox-seo'),
                __('Featured images appear in search results and social media shares.', 'aeox-seo')
            );
        }
        
        // Content issues
        $content = $analysis['content'];
        if ($content['word_count'] < 300) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'thin_content',
                self::SEVERITY_WARNING,
                'content',
                sprintf(__('Content is too short (%d words)', 'aeox-seo'), $content['word_count']),
                __('At least 300 words', 'aeox-seo'),
                __('Expand your content to provide more value to readers.', 'aeox-seo'),
                __('Longer content tends to rank better and provides more value.', 'aeox-seo')
            );
        }
        
        // Readability issues
        $readability = $analysis['readability'];
        if ($readability['flesch_reading_ease'] < 50 && $readability['word_count'] > 0) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'readability_difficult',
                self::SEVERITY_NOTICE,
                'content',
                sprintf(__('Content may be difficult to read (Score: %d)', 'aeox-seo'), round($readability['flesch_reading_ease'])),
                __('Flesch score of 60+', 'aeox-seo'),
                __('Use shorter sentences and simpler words to improve readability.', 'aeox-seo'),
                __('Easier-to-read content reaches a wider audience.', 'aeox-seo')
            );
        }
        
        if ($readability['passive_voice_percentage'] > 15) {
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'passive_voice_high',
                self::SEVERITY_NOTICE,
                'content',
                sprintf(__('High passive voice usage (%.1f%%)', 'aeox-seo'), $readability['passive_voice_percentage']),
                __('Less than 10% passive voice', 'aeox-seo'),
                __('Use active voice to make your writing more engaging.', 'aeox-seo'),
                __('Active voice is more direct and engaging for readers.', 'aeox-seo')
            );
        }
        
        // Technical issues
        $technical = $analysis['technical'];
        if (!$technical['is_public']) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'post_not_public',
                self::SEVERITY_CRITICAL,
                'technical',
                __('Post type is not public', 'aeox-seo'),
                __('Public post type', 'aeox-seo'),
                __('This content type cannot be indexed by search engines.', 'aeox-seo'),
                __('Only public post types can be indexed.', 'aeox-seo')
            );
        }
        
        if (!$technical['is_indexable']) {
            $issues[] = $this->create_issue(
                ++$issue_id,
                'noindex_set',
                self::SEVERITY_WARNING,
                'technical',
                __('Page is set to noindex', 'aeox-seo'),
                __('Indexable page', 'aeox-seo'),
                __('Remove the noindex setting if you want this page in search results.', 'aeox-seo'),
                __('Noindex prevents search engines from indexing this page.', 'aeox-seo')
            );
        }
        
        if (!$technical['has_ssl'] && strpos(get_site_url(), 'https') === 0) {
            // This is unlikely but check anyway
            $opportunities[] = $this->create_issue(
                ++$issue_id,
                'ssl_mixed',
                self::SEVERITY_WARNING,
                'technical',
                __('SSL certificate issue detected', 'aeox-seo'),
                __('HTTPS everywhere', 'aeox-seo'),
                __('Ensure all resources load over HTTPS.', 'aeox-seo'),
                __('HTTPS is a ranking factor and improves security.', 'aeox-seo')
            );
        }
        
        return array(
            'issues' => $issues,
            'opportunities' => $opportunities,
        );
    }
    
    /**
     * Create standardized issue object
     */
    private function create_issue($id, $code, $severity, $category, $title, $expected, $recommendation, $why) {
        return array(
            'id' => $id,
            'code' => $code,
            'severity' => $severity,
            'category' => $category,
            'title' => $title,
            'detected_value' => '',
            'expected_value' => $expected,
            'description' => $recommendation,
            'why_it_matters' => $why,
            'auto_fix_available' => false,
        );
    }
    
    /**
     * Generate prioritized recommendations
     */
    private function generate_recommendations($issues) {
        $recommendations = array();
        
        // Priority: Critical > Warning > Notice > Info
        $all_issues = array_merge($issues['issues'], $issues['opportunities']);
        
        usort($all_issues, function($a, $b) {
            $priority = array(
                self::SEVERITY_CRITICAL => 1,
                self::SEVERITY_WARNING => 2,
                self::SEVERITY_NOTICE => 3,
                self::SEVERITY_INFO => 4,
            );
            return $priority[$a['severity']] - $priority[$b['severity']];
        });
        
        foreach ($all_issues as $issue) {
            $recommendations[] = array(
                'priority' => $issue['severity'],
                'action' => $issue['description'],
                'impact' => $issue['why_it_matters'],
            );
        }
        
        return array_slice($recommendations, 0, 5); // Top 5 recommendations
    }
    
    /**
     * Save analysis to database
     */
    private function save_analysis($post_id, $scores, $analysis) {
        $db = AEOX_Database::get_instance();
        
        // Save overall scores
        $db->save_analysis($post_id, $scores);
        
        // Save links
        $this->save_links($post_id, $analysis['links']);
        
        // Save issues to database
        $this->save_issues($post_id, $analysis);
    }
    
    /**
     * Save links to database
     */
    private function save_links($post_id, $links) {
        global $wpdb;
        $table = $wpdb->prefix . 'aeox_links';
        
        // Clear old links
        $wpdb->delete($table, array('post_id' => $post_id), array('%d'));
        
        // Insert internal links
        foreach ($links['internal_urls'] as $link) {
            $wpdb->insert($table, array(
                'post_id' => $post_id,
                'link_type' => 'internal',
                'link_url' => $link['url'],
                'link_text' => $link['text'],
                'is_follow' => !$link['nofollow'],
            ), array('%d', '%s', '%s', '%s', '%d'));
        }
        
        // Insert external links
        foreach ($links['external_urls'] as $link) {
            $wpdb->insert($table, array(
                'post_id' => $post_id,
                'link_type' => 'external',
                'link_url' => $link['url'],
                'link_text' => $link['text'],
                'is_follow' => !$link['nofollow'],
            ), array('%d', '%s', '%s', '%s', '%d'));
        }
    }
    
    /**
     * Save issues to database
     */
    private function save_issues($post_id, $analysis) {
        $db = AEOX_Database::get_instance();
        $scores = $this->calculate_scores($analysis);
        $issues_data = $this->generate_issues($analysis, $scores);
        
        // Clear old unresolved issues
        global $wpdb;
        $table = $wpdb->prefix . 'aeox_issues';
        $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE post_id = %d AND is_resolved = 0", $post_id));
        
        // Insert new issues
        foreach ($issues_data['issues'] as $issue) {
            $db->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => $issue['code'],
                'severity' => $issue['severity'],
                'category' => $issue['category'],
                'detected_value' => $issue['detected_value'],
                'expected_value' => $issue['expected_value'],
                'description' => $issue['description'],
                'how_to_fix' => $issue['why_it_matters'],
                'is_resolved' => 0,
            ));
        }
    }
    
    /**
     * Get empty result for errors
     */
    private function get_empty_result() {
        return array(
            'post_id' => 0,
            'scores' => array(
                'seo' => 0,
                'content' => 0,
                'technical' => 0,
                'overall' => 0,
            ),
            'analysis' => array(),
            'issues' => array(),
            'opportunities' => array(),
            'recommendations' => array(),
            'analyzed_at' => current_time('mysql'),
        );
    }
    
    /**
     * Trigger post analysis (for save_post hook)
     */
    public function analyze_post($post_id) {
        $post_id = absint($post_id);
        
        if (empty($post_id)) {
            return;
        }
        
        // Don't analyze revisions or autosaves
        if (wp_is_post_revision($post_id) || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        // Run analysis
        $this->analyze($post_id);
    }
}
