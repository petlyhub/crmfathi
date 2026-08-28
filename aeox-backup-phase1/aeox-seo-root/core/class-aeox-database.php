<?php
/**
 * Database Handler for AEOX SEO
 * Creates and manages custom database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AEOX_Database {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            'wp_aeox_analysis' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_analysis (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                seo_score int(3) DEFAULT 0,
                aeo_score int(3) DEFAULT 0,
                geo_score int(3) DEFAULT 0,
                schema_score int(3) DEFAULT 0,
                entity_score int(3) DEFAULT 0,
                content_score int(3) DEFAULT 0,
                overall_score int(3) DEFAULT 0,
                analyzed_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY analyzed_at (analyzed_at)
            ) $charset_collate;",
            
            'wp_aeox_entities' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_entities (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED DEFAULT NULL,
                entity_type varchar(50) NOT NULL,
                entity_name varchar(255) NOT NULL,
                entity_data longtext,
                confidence_score float DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY entity_type (entity_type)
            ) $charset_collate;",
            
            'wp_aeox_schema' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_schema (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                schema_type varchar(50) NOT NULL,
                schema_data longtext NOT NULL,
                is_valid tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY schema_type (schema_type)
            ) $charset_collate;",
            
            'wp_aeox_keywords' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_keywords (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                keyword varchar(255) NOT NULL,
                keyword_type varchar(50) DEFAULT 'focus',
                density float DEFAULT 0,
                occurrences int(11) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY keyword (keyword(191))
            ) $charset_collate;",
            
            'wp_aeox_questions' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_questions (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                question text NOT NULL,
                has_answer tinyint(1) DEFAULT 0,
                answer_quality int(3) DEFAULT 0,
                question_type varchar(50) DEFAULT 'what',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id)
            ) $charset_collate;",
            
            'wp_aeox_facts' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_facts (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                fact_statement text NOT NULL,
                has_source tinyint(1) DEFAULT 0,
                source_url varchar(500) DEFAULT NULL,
                confidence_score float DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id)
            ) $charset_collate;",
            
            'wp_aeox_citations' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_citations (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                citation_readiness int(3) DEFAULT 0,
                factual_clarity int(3) DEFAULT 0,
                entity_clarity int(3) DEFAULT 0,
                source_support int(3) DEFAULT 0,
                content_depth int(3) DEFAULT 0,
                freshness int(3) DEFAULT 0,
                structure int(3) DEFAULT 0,
                total_geo_score int(3) DEFAULT 0,
                checked_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id)
            ) $charset_collate;",
            
            'wp_aeox_links' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_links (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                link_type varchar(20) NOT NULL,
                link_url varchar(500) NOT NULL,
                link_text varchar(255) DEFAULT NULL,
                is_follow tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY link_type (link_type)
            ) $charset_collate;",
            
            'wp_aeox_ai_visibility' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_ai_visibility (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                google_ai_score int(3) DEFAULT 0,
                bing_copilot_score int(3) DEFAULT 0,
                chatgpt_score int(3) DEFAULT 0,
                perplexity_score int(3) DEFAULT 0,
                citations_count int(11) DEFAULT 0,
                last_checked datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id)
            ) $charset_collate;",
            
            'wp_aeox_issues' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aeox_issues (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED DEFAULT NULL,
                issue_type varchar(50) NOT NULL,
                severity varchar(20) NOT NULL,
                category varchar(50) NOT NULL,
                detected_value text,
                expected_value text,
                description text,
                how_to_fix text,
                is_resolved tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                resolved_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY severity (severity),
                KEY is_resolved (is_resolved)
            ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_name => $sql) {
            dbDelta($sql);
        }
        
        // Set version option
        update_option('aeox_db_version', AEOX_VERSION);
    }
    
    /**
     * Get analysis data for a post
     */
    public function get_analysis($post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aeox_analysis';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d ORDER BY analyzed_at DESC LIMIT 1",
            $post_id
        ));
    }
    
    /**
     * Save analysis data
     */
    public function save_analysis($post_id, $scores) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aeox_analysis';
        
        $data = array(
            'post_id' => $post_id,
            'seo_score' => isset($scores['seo']) ? $scores['seo'] : 0,
            'aeo_score' => isset($scores['aeo']) ? $scores['aeo'] : 0,
            'geo_score' => isset($scores['geo']) ? $scores['geo'] : 0,
            'schema_score' => isset($scores['schema']) ? $scores['schema'] : 0,
            'entity_score' => isset($scores['entity']) ? $scores['entity'] : 0,
            'content_score' => isset($scores['content']) ? $scores['content'] : 0,
            'overall_score' => isset($scores['overall']) ? $scores['overall'] : 0
        );
        
        $format = array(
            '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d'
        );
        
        $wpdb->insert($table, $data, $format);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get all issues for a post
     */
    public function get_issues($post_id = null, $resolved = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aeox_issues';
        
        $where = array('is_resolved' => $resolved ? 1 : 0);
        
        if ($post_id) {
            $where['post_id'] = $post_id;
        }
        
        $where_clause = array();
        $values = array();
        
        foreach ($where as $key => $value) {
            $where_clause[] = "$key = %d";
            $values[] = $value;
        }
        
        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where_clause) . " ORDER BY 
            CASE severity 
                WHEN 'critical' THEN 1 
                WHEN 'warning' THEN 2 
                WHEN 'notice' THEN 3 
            END ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }
    
    /**
     * Add an issue
     */
    public function add_issue($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aeox_issues';
        
        $format = array(
            '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
        );
        
        return $wpdb->insert($table, $data, $format);
    }
}
