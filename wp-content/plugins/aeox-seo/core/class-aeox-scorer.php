<?php
/**
 * Scorer for AEOX SEO
 * Calculates weighted scores for different engines
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AEOX_Scorer {
    
    private static $instance = null;
    
    /**
     * Score weights (as per architecture document)
     */
    private $weights = array(
        'seo' => 0.20,
        'aeo' => 0.20,
        'geo' => 0.20,
        'schema' => 0.10,
        'entity' => 0.10,
        'content' => 0.15,
        'freshness' => 0.05
    );
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Calculate overall score from individual scores
     */
    public function calculate_overall($scores) {
        $overall = 0;
        
        foreach ($this->weights as $category => $weight) {
            if (isset($scores[$category])) {
                $overall += $scores[$category] * $weight;
            }
        }
        
        return round($overall);
    }
    
    /**
     * Calculate SEO score
     */
    public function calculate_seo_score($data) {
        $score = 0;
        $max_score = 0;
        
        // Technical SEO checks
        $checks = array(
            'has_title' => array('value' => isset($data['title']) && !empty($data['title']), 'weight' => 10),
            'title_length' => array('value' => isset($data['title_length']) && $data['title_length'] >= 30 && $data['title_length'] <= 60, 'weight' => 5),
            'has_meta_description' => array('value' => isset($data['meta_description']) && !empty($data['meta_description']), 'weight' => 10),
            'meta_description_length' => array('value' => isset($data['meta_description_length']) && $data['meta_description_length'] >= 120 && $data['meta_description_length'] <= 160, 'weight' => 5),
            'has_h1' => array('value' => isset($data['has_h1']) && $data['has_h1'], 'weight' => 10),
            'has_h2' => array('value' => isset($data['has_h2']) && $data['has_h2'], 'weight' => 5),
            'has_internal_links' => array('value' => isset($data['internal_links']) && $data['internal_links'] > 0, 'weight' => 8),
            'has_external_links' => array('value' => isset($data['external_links']) && $data['external_links'] > 0, 'weight' => 5),
            'has_images' => array('value' => isset($data['images']) && $data['images'] > 0, 'weight' => 3),
            'images_with_alt' => array('value' => isset($data['images_with_alt']) && $data['images_with_alt'] == $data['images'], 'weight' => 7),
            'word_count' => array('value' => isset($data['word_count']) && $data['word_count'] >= 300, 'weight' => 10),
            'has_canonical' => array('value' => isset($data['canonical']) && !empty($data['canonical']), 'weight' => 5),
            'is_indexable' => array('value' => !isset($data['noindex']) || !$data['noindex'], 'weight' => 10),
            'https' => array('value' => is_ssl(), 'weight' => 7)
        );
        
        foreach ($checks as $check) {
            $max_score += $check['weight'];
            if ($check['value']) {
                $score += $check['weight'];
            }
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Calculate AEO score
     */
    public function calculate_aeo_score($data) {
        $score = 0;
        $max_score = 0;
        
        $checks = array(
            'has_direct_answer' => array('value' => isset($data['has_direct_answer']) && $data['has_direct_answer'], 'weight' => 20),
            'answer_clarity' => array('value' => isset($data['answer_clarity']) && $data['answer_clarity'] >= 7, 'weight' => 15),
            'has_questions' => array('value' => isset($data['questions_count']) && $data['questions_count'] > 0, 'weight' => 10),
            'questions_answered' => array('value' => isset($data['questions_answered_ratio']) && $data['questions_answered_ratio'] >= 0.8, 'weight' => 15),
            'has_introduction' => array('value' => isset($data['has_introduction']) && $data['has_introduction'], 'weight' => 10),
            'has_summary' => array('value' => isset($data['has_summary']) && $data['has_summary'], 'weight' => 10),
            'readability_score' => array('value' => isset($data['readability_score']) && $data['readability_score'] >= 70, 'weight' => 10),
            'has_lists' => array('value' => isset($data['has_lists']) && $data['has_lists'], 'weight' => 5),
            'has_tables' => array('value' => isset($data['has_tables']) && $data['has_tables'], 'weight' => 5)
        );
        
        foreach ($checks as $check) {
            $max_score += $check['weight'];
            if ($check['value']) {
                $score += $check['weight'];
            }
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Calculate GEO score
     */
    public function calculate_geo_score($data) {
        $score = 0;
        $max_score = 0;
        
        $checks = array(
            'citation_readiness' => array('value' => isset($data['citation_readiness']) && $data['citation_readiness'] >= 70, 'weight' => 15),
            'factual_clarity' => array('value' => isset($data['factual_clarity']) && $data['factual_clarity'] >= 70, 'weight' => 15),
            'entity_clarity' => array('value' => isset($data['entity_clarity']) && $data['entity_clarity'] >= 70, 'weight' => 10),
            'has_sources' => array('value' => isset($data['facts_with_sources']) && $data['facts_with_sources'] > 0, 'weight' => 10),
            'content_depth' => array('value' => isset($data['content_depth']) && $data['content_depth'] >= 70, 'weight' => 15),
            'has_structure' => array('value' => isset($data['has_structure']) && $data['has_structure'], 'weight' => 10),
            'is_fresh' => array('value' => isset($data['is_fresh']) && $data['is_fresh'], 'weight' => 10),
            'has_internal_links' => array('value' => isset($data['internal_links']) && $data['internal_links'] >= 3, 'weight' => 5),
            'has_schema' => array('value' => isset($data['has_schema']) && $data['has_schema'], 'weight' => 10)
        );
        
        foreach ($checks as $check) {
            $max_score += $check['weight'];
            if ($check['value']) {
                $score += $check['weight'];
            }
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Calculate Schema score
     */
    public function calculate_schema_score($data) {
        $score = 0;
        $max_score = 0;
        
        $checks = array(
            'has_schema' => array('value' => isset($data['has_schema']) && $data['has_schema'], 'weight' => 20),
            'schema_valid' => array('value' => isset($data['schema_valid']) && $data['schema_valid'], 'weight' => 30),
            'organization_schema' => array('value' => isset($data['organization_schema']) && $data['organization_schema'], 'weight' => 15),
            'article_schema' => array('value' => isset($data['article_schema']) && $data['article_schema'], 'weight' => 10),
            'breadcrumb_schema' => array('value' => isset($data['breadcrumb_schema']) && $data['breadcrumb_schema'], 'weight' => 10),
            'website_schema' => array('value' => isset($data['website_schema']) && $data['website_schema'], 'weight' => 10),
            'graph_complete' => array('value' => isset($data['graph_complete']) && $data['graph_complete'], 'weight' => 5)
        );
        
        foreach ($checks as $check) {
            $max_score += $check['weight'];
            if ($check['value']) {
                $score += $check['weight'];
            }
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Calculate Entity score
     */
    public function calculate_entity_score($data) {
        $score = 0;
        $max_score = 0;
        
        $checks = array(
            'has_organization' => array('value' => isset($data['organization_entity']) && $data['organization_entity'], 'weight' => 20),
            'has_author' => array('value' => isset($data['author_entity']) && $data['author_entity'], 'weight' => 15),
            'entity_name_clear' => array('value' => isset($data['entity_name_clear']) && $data['entity_name_clear'], 'weight' => 15),
            'has_same_as' => array('value' => isset($data['same_as']) && count($data['same_as']) > 0, 'weight' => 15),
            'has_logo' => array('value' => isset($data['logo']) && !empty($data['logo']), 'weight' => 10),
            'has_contact' => array('value' => isset($data['contact_info']) && !empty($data['contact_info']), 'weight' => 10),
            'entity_consistency' => array('value' => isset($data['entity_consistency']) && $data['entity_consistency'] >= 80, 'weight' => 15)
        );
        
        foreach ($checks as $check) {
            $max_score += $check['weight'];
            if ($check['value']) {
                $score += $check['weight'];
            }
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Calculate Content score
     */
    public function calculate_content_score($data) {
        $score = 0;
        $max_score = 0;
        
        $checks = array(
            'word_count' => array('value' => isset($data['word_count']) && $data['word_count'] >= 500, 'weight' => 15),
            'has_headings' => array('value' => isset($data['has_headings']) && $data['has_headings'], 'weight' => 15),
            'heading_hierarchy' => array('value' => isset($data['heading_hierarchy']) && $data['heading_hierarchy'], 'weight' => 10),
            'has_paragraphs' => array('value' => isset($data['has_paragraphs']) && $data['has_paragraphs'], 'weight' => 10),
            'paragraph_length' => array('value' => isset($data['paragraph_length_ok']) && $data['paragraph_length_ok'], 'weight' => 10),
            'has_media' => array('value' => isset($data['has_media']) && $data['has_media'], 'weight' => 10),
            'topic_coverage' => array('value' => isset($data['topic_coverage']) && $data['topic_coverage'] >= 70, 'weight' => 20),
            'uniqueness' => array('value' => isset($data['uniqueness']) && $data['uniqueness'] >= 80, 'weight' => 10)
        );
        
        foreach ($checks as $check) {
            $max_score += $check['weight'];
            if ($check['value']) {
                $score += $check['weight'];
            }
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Get score grade
     */
    public function get_grade($score) {
        if ($score >= 90) {
            return array('grade' => 'A', 'label' => __('Excellent', 'aeox-seo'), 'color' => '#4caf50');
        } elseif ($score >= 80) {
            return array('grade' => 'B', 'label' => __('Good', 'aeox-seo'), 'color' => '#8bc34a');
        } elseif ($score >= 70) {
            return array('grade' => 'C', 'label' => __('Fair', 'aeox-seo'), 'color' => '#ffeb3b');
        } elseif ($score >= 60) {
            return array('grade' => 'D', 'label' => __('Poor', 'aeox-seo'), 'color' => '#ff9800');
        } else {
            return array('grade' => 'F', 'label' => __('Critical', 'aeox-seo'), 'color' => '#f44336');
        }
    }
    
    /**
     * Get weights
     */
    public function get_weights() {
        return $this->weights;
    }
}
