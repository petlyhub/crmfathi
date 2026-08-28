<?php
/**
 * AEOX Database Manager
 *
 * Handles creation and management of custom database tables.
 *
 * @package AEOX_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AEOX_Database {

    /**
     * Get table names with prefix
     */
    public function get_tables() {
        global $wpdb;
        
        return [
            'analysis'   => $wpdb->prefix . 'aeox_analysis',
            'entities'   => $wpdb->prefix . 'aeox_entities',
            'schema'     => $wpdb->prefix . 'aeox_schema',
            'keywords'   => $wpdb->prefix . 'aeox_keywords',
            'questions'  => $wpdb->prefix . 'aeox_questions',
            'facts'      => $wpdb->prefix . 'aeox_facts',
            'citations'  => $wpdb->prefix . 'aeox_citations',
            'links'      => $wpdb->prefix . 'aeox_links',
            'issues'     => $wpdb->prefix . 'aeox_issues',
        ];
    }

    /**
     * Create all database tables
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tables = $this->get_tables();

        // wp_aeox_analysis - Stores analysis scores for each post
        $sql_analysis = "CREATE TABLE {$tables['analysis']} (
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
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY analyzed_at (analyzed_at)
        ) $charset_collate;";

        // wp_aeox_entities - Stores detected entities
        $sql_entities = "CREATE TABLE {$tables['entities']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            entity_name varchar(255) NOT NULL,
            entity_type varchar(100) DEFAULT NULL,
            confidence float DEFAULT 0.5,
            mentions int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY entity_name (entity_name)
        ) $charset_collate;";

        // wp_aeox_questions - Stores detected questions
        $sql_questions = "CREATE TABLE {$tables['questions']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            question text NOT NULL,
            answer text DEFAULT NULL,
            is_answered tinyint(1) DEFAULT 0,
            source_block varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // wp_aeox_issues - Stores detected issues
        $sql_issues = "CREATE TABLE {$tables['issues']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            issue_type varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            message text NOT NULL,
            fix_suggestion text DEFAULT NULL,
            is_resolved tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY severity (severity),
            KEY is_resolved (is_resolved)
        ) $charset_collate;";

        dbDelta($sql_analysis);
        dbDelta($sql_entities);
        dbDelta($sql_questions);
        dbDelta($sql_issues);

        // Note: Additional tables can be added as needed in future versions
    }

    /**
     * Drop all tables on uninstall
     */
    public function drop_tables() {
        global $wpdb;
        $tables = $this->get_tables();

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Save analysis data
     *
     * @param int   $post_id Post ID.
     * @param array $scores  Array of scores.
     * @return int Inserted/Updated row ID.
     */
    public function save_analysis($post_id, $scores) {
        global $wpdb;
        $table = $this->get_tables()['analysis'];

        $data = [
            'post_id'        => $post_id,
            'seo_score'      => $scores['seo_score'] ?? 0,
            'aeo_score'      => $scores['aeo_score'] ?? 0,
            'geo_score'      => $scores['geo_score'] ?? 0,
            'schema_score'   => $scores['schema_score'] ?? 0,
            'entity_score'   => $scores['entity_score'] ?? 0,
            'content_score'  => $scores['content_score'] ?? 0,
            'overall_score'  => $scores['overall_score'] ?? 0,
        ];

        // Check if exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE post_id = %d ORDER BY analyzed_at DESC LIMIT 1",
            $post_id
        ));

        if ($existing) {
            $wpdb->update($table, $data, ['id' => $existing]);
            return $existing;
        } else {
            $data['analyzed_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Get latest analysis for a post
     *
     * @param int $post_id Post ID.
     * @return object|null Analysis data or null.
     */
    public function get_analysis($post_id) {
        global $wpdb;
        $table = $this->get_tables()['analysis'];

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE post_id = %d ORDER BY analyzed_at DESC LIMIT 1",
            $post_id
        ));
    }
}
