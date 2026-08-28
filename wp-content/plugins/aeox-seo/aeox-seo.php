<?php
/**
 * Plugin Name: AEOX SEO
 * Plugin URI: https://aeoxseo.com
 * Description: AI Search Optimization Platform for WordPress - SEO + AEO + GEO + Entity Optimization
 * Version: 1.0.0
 * Author: AEOX Team
 * Author URI: https://aeoxseo.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aeox
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AEOX_VERSION', '1.0.0');
define('AEOX_PLUGIN_FILE', __FILE__);
define('AEOX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AEOX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AEOX_PLUGIN_BASENAME', plugin_basename(__FILE__));

final class AEOX_SEO {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        register_activation_hook(AEOX_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(AEOX_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'), 0);
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_bar_menu', array($this, 'admin_bar_menu'), 100);
        add_filter('plugin_action_links_' . AEOX_PLUGIN_BASENAME, array($this, 'action_links'));
    }
    
    private function load_dependencies() {
        require_once AEOX_PLUGIN_DIR . 'core/class-aeox-database.php';
        require_once AEOX_PLUGIN_DIR . 'core/class-aeox-cache.php';
        require_once AEOX_PLUGIN_DIR . 'core/class-aeox-scorer.php';
        
        require_once AEOX_PLUGIN_DIR . 'engines/seo/class-aeox-seo-engine.php';
        require_once AEOX_PLUGIN_DIR . 'engines/aeo/class-aeox-aeo-engine.php';
        require_once AEOX_PLUGIN_DIR . 'engines/geo/class-aeox-geo-module.php';
        
        if (is_admin()) {
            require_once AEOX_PLUGIN_DIR . 'admin/class-dashboard.php';
        }
    }
    
    public function init() {
        AEOX_Database::get_instance()->create_tables();
        
        if (class_exists('AEOX_SEO_Engine')) {
            AEOX_SEO_Engine::get_instance();
        }
        if (class_exists('AEOX_AEO_Engine')) {
            AEOX_AEO_Engine::get_instance();
        }
        if (class_exists('AEOX_GEO_Module')) {
            AEOX_GEO_Module::get_instance();
        }
    }
    
    public function admin_init() {
        // Admin initialization will be handled by dashboard class
    }
    
    public function activate() {
        AEOX_Database::get_instance()->create_tables();
        
        add_option('aeox_version', AEOX_VERSION);
        add_option('aeox_settings', array(
            'enable_seo' => true,
            'enable_aeo' => true,
            'enable_geo' => true,
            'enable_schema' => true,
            'enable_entity' => true,
            'remove_data_on_uninstall' => false,
        ));
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('aeox', false, dirname(AEOX_PLUGIN_BASENAME) . '/languages');
    }
    
    public function add_admin_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_menu_page(
            __('AEOX SEO', 'aeox'),
            __('AEOX SEO', 'aeox'),
            'manage_options',
            'aeox-seo',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            90
        );
        
        add_submenu_page(
            'aeox-seo',
            __('Dashboard', 'aeox'),
            __('Dashboard', 'aeox'),
            'manage_options',
            'aeox-seo',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'aeox-seo',
            __('Settings', 'aeox'),
            __('Settings', 'aeox'),
            'manage_options',
            'aeox-settings',
            array($this, 'render_settings')
        );
    }
    
    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aeox'));
        }
        require_once AEOX_PLUGIN_DIR . 'admin/class-dashboard.php';
        AEOX_Dashboard::render();
    }
    
    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aeox'));
        }
        echo '<div class="wrap"><h1>' . esc_html__('AEOX SEO Settings', 'aeox') . '</h1>';
        echo '<p>' . esc_html__('Settings page coming soon.', 'aeox') . '</p></div>';
    }
    
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'aeox-seo-meta-box',
                __('AEOX SEO Analysis', 'aeox'),
                array($this, 'render_meta_box'),
                $post_type->name,
                'side',
                'high'
            );
        }
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('aeox_save_meta_box', 'aeox_meta_box_nonce');
        
        $meta_title = get_post_meta($post->ID, '_aeox_meta_title', true);
        $meta_description = get_post_meta($post->ID, '_aeox_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_aeox_focus_keyword', true);
        
        echo '<label for="aeox_focus_keyword">' . esc_html__('Focus Keyword:', 'aeox') . '</label>';
        echo '<input type="text" id="aeox_focus_keyword" name="aeox_focus_keyword" value="' . esc_attr($focus_keyword) . '" style="width:100%" />';
        
        echo '<label for="aeox_meta_title" style="display:block;margin-top:10px;">' . esc_html__('Meta Title:', 'aeox') . '</label>';
        echo '<textarea id="aeox_meta_title" name="aeox_meta_title" rows="2" style="width:100%">' . esc_textarea($meta_title) . '</textarea>';
        
        echo '<label for="aeox_meta_description" style="display:block;margin-top:10px;">' . esc_html__('Meta Description:', 'aeox') . '</label>';
        echo '<textarea id="aeox_meta_description" name="aeox_meta_description" rows="3" style="width:100%">' . esc_textarea($meta_description) . '</textarea>';
    }
    
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['aeox_meta_box_nonce']) || !wp_verify_nonce($_POST['aeox_meta_box_nonce'], 'aeox_save_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['aeox_focus_keyword'])) {
            update_post_meta($post_id, '_aeox_focus_keyword', sanitize_text_field($_POST['aeox_focus_keyword']));
        }
        
        if (isset($_POST['aeox_meta_title'])) {
            update_post_meta($post_id, '_aeox_meta_title', sanitize_text_field($_POST['aeox_meta_title']));
        }
        
        if (isset($_POST['aeox_meta_description'])) {
            update_post_meta($post_id, '_aeox_meta_description', sanitize_textarea_field($_POST['aeox_meta_description']));
        }
        
        if (class_exists('AEOX_SEO_Engine')) {
            AEOX_SEO_Engine::get_instance()->analyze_post($post_id);
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'aeox') === false) {
            return;
        }
        
        wp_enqueue_style('aeox-admin-css', AEOX_PLUGIN_URL . 'assets/css/admin.css', array(), AEOX_VERSION);
        wp_enqueue_script('aeox-admin-js', AEOX_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AEOX_VERSION, true);
        
        wp_localize_script('aeox-admin-js', 'aeoxAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aeox_admin_nonce'),
            'strings' => array(
                'analyzing' => __('Analyzing...', 'aeox'),
                'complete' => __('Analysis Complete', 'aeox'),
                'error' => __('Analysis Error', 'aeox')
            )
        ));
    }
    
    public function admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'aeox-seo',
            'title' => '<span class="ab-icon dashicons dashicons-chart-line"></span><span class="ab-label">' . __('AEOX SEO', 'aeox') . '</span>',
            'href' => admin_url('admin.php?page=aeox-seo')
        ));
        
        if (is_singular()) {
            global $post;
            $wp_admin_bar->add_node(array(
                'id' => 'aeox-analyze',
                'parent' => 'aeox-seo',
                'title' => __('Analyze This Page', 'aeox'),
                'href' => admin_url('post.php?post=' . intval($post->ID) . '&action=edit#aeox-seo-meta-box')
            ));
        }
    }
    
    public function action_links($links) {
        $custom_links = array(
            '<a href="' . esc_url(admin_url('admin.php?page=aeox-settings')) . '">' . __('Settings', 'aeox') . '</a>',
            '<a href="' . esc_url(admin_url('admin.php?page=aeox-seo')) . '">' . __('Dashboard', 'aeox') . '</a>'
        );
        
        return array_merge($custom_links, $links);
    }
}

function aeox_seo_init() {
    return AEOX_SEO::get_instance();
}

$GLOBALS['aeox_seo'] = aeox_seo_init();
