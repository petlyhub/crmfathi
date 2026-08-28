<?php
/**
 * AEOX SEO Uninstall Handler
 * 
 * This file runs when the plugin is deleted from WordPress.
 * It cleans up database tables and options if the user has enabled data removal.
 * 
 * @package AEOX_SEO
 * @since 1.0.0
 */

// Security check - must be called from WordPress uninstall process
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load WordPress
global $wpdb;

// Get settings to check if we should remove data
$remove_data = get_option('aeox_settings_remove_data_on_uninstall', false);

// Also check legacy option name
if (!$remove_data) {
    $old_settings = get_option('aeox_settings', array());
    $remove_data = isset($old_settings['remove_data_on_uninstall']) && $old_settings['remove_data_on_uninstall'];
}

// Only remove data if explicitly enabled by user
if ($remove_data) {
    
    // Drop all custom tables
    $tables = array(
        'aeox_analysis',
        'aeox_entities',
        'aeox_schema',
        'aeox_keywords',
        'aeox_questions',
        'aeox_facts',
        'aeox_citations',
        'aeox_links',
        'aeox_ai_visibility',
        'aeox_issues',
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }
    
    // Delete all plugin options
    delete_option('aeox_version');
    delete_option('aeox_settings');
    delete_option('aeox_db_version');
    
    // Delete all post meta created by AEOX
    $post_meta_keys = array(
        '_aeox_focus_keyword',
        '_aeox_meta_title',
        '_aeox_meta_description',
        '_aeox_seo_score',
        '_aeox_aeo_score',
        '_aeox_geo_score',
        '_aeox_overall_score',
    );
    
    foreach ($post_meta_keys as $meta_key) {
        $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => $meta_key),
            array('%s')
        );
    }
    
    // Clear any transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aeox_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_aeox_%'");
    
} else {
    // If not removing data, just deactivate cleanly
    // Tables and data remain for potential reactivation
}

// Flush rewrite rules (handled by deactivation hook usually, but ensure clean state)
// Note: Can't call flush_rewrite_rules() here as WP may not be fully loaded
