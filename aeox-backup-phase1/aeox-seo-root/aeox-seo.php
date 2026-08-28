<?php
/**
 * Plugin Name: AEOX SEO
 * Plugin URI: https://aeoxseo.com
 * Description: AI Search Optimization Platform for WordPress - SEO + AEO + GEO + Entity Optimization
 * Version: 0.1.0
 * Author: AEOX Team
 * Author URI: https://aeoxseo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aeox-seo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AEOX_VERSION', '0.1.0');
define('AEOX_PLUGIN_FILE', __FILE__);
define('AEOX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AEOX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AEOX_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AEOX SEO Class
 */
final class AEOX_SEO {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(AEOX_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(AEOX_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'), 0);
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Add meta box to posts/pages
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        
        // Add to admin bar
        add_action('admin_bar_menu', array($this, 'admin_bar_menu'), 100);
        
        // Add action links
        add_filter('plugin_action_links_' . AEOX_PLUGIN_BASENAME, array($this, 'action_links'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core files
        require_once AEOX_PLUGIN_DIR . 'core/class-aeox-database.php';
        require_once AEOX_PLUGIN_DIR . 'core/class-aeox-cache.php';
        require_once AEOX_PLUGIN_DIR . 'core/class-aeox-scorer.php';
        
        // Engine files
        require_once AEOX_PLUGIN_DIR . 'engines/seo/class-aeox-seo-engine.php';
        require_once AEOX_PLUGIN_DIR . 'engines/aeo/class-aeox-aeo-engine.php';
        require_once AEOX_PLUGIN_DIR . 'engines/geo/class-aeox-geo-engine.php';
        require_once AEOX_PLUGIN_DIR . 'engines/schema/class-aeox-schema-engine.php';
        require_once AEOX_PLUGIN_DIR . 'engines/entity/class-aeox-entity-engine.php';
        
        // Admin files
        require_once AEOX_PLUGIN_DIR . 'admin/class-aeox-admin.php';
        require_once AEOX_PLUGIN_DIR . 'admin/class-aeox-dashboard.php';
        require_once AEOX_PLUGIN_DIR . 'admin/class-aeox-settings.php';
        require_once AEOX_PLUGIN_DIR . 'admin/class-aeox-meta-box.php';
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize database tables
        AEOX_Database::get_instance()->create_tables();
        
        // Initialize engines
        AEOX_SEO_Engine::get_instance();
        AEOX_AEO_Engine::get_instance();
        AEOX_GEO_Engine::get_instance();
        AEOX_Schema_Engine::get_instance();
        AEOX_Entity_Engine::get_instance();
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        AEOX_Admin::get_instance()->init();
        AEOX_Settings::get_instance()->init();
    }
    
    /**
     * Activation hook
     */
    public function activate() {
        AEOX_Database::get_instance()->create_tables();
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation hook
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('aeox-seo', false, dirname(AEOX_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AEOX SEO', 'aeox-seo'),
            __('AEOX SEO', 'aeox-seo'),
            'manage_options',
            'aeox-seo',
            array(AEOX_Dashboard::get_instance(), 'render'),
            'dashicons-chart-line',
            90
        );
        
        // Dashboard submenu
        add_submenu_page(
            'aeox-seo',
            __('Dashboard', 'aeox-seo'),
            __('Dashboard', 'aeox-seo'),
            'manage_options',
            'aeox-seo',
            array(AEOX_Dashboard::get_instance(), 'render')
        );
        
        // Analysis submenu
        add_submenu_page(
            'aeox-seo',
            __('Content Analysis', 'aeox-seo'),
            __('Content Analysis', 'aeox-seo'),
            'manage_options',
            'aeox-analysis',
            array(AEOX_Dashboard::get_instance(), 'render_analysis')
        );
        
        // Settings submenu
        add_submenu_page(
            'aeox-seo',
            __('Settings', 'aeox-seo'),
            __('Settings', 'aeox-seo'),
            'manage_options',
            'aeox-settings',
            array(AEOX_Settings::get_instance(), 'render')
        );
        
        // Tools submenu
        add_submenu_page(
            'aeox-seo',
            __('Tools', 'aeox-seo'),
            __('Tools', 'aeox-seo'),
            'manage_options',
            'aeox-tools',
            array(AEOX_Dashboard::get_instance(), 'render_tools')
        );
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'aeox-seo-meta-box',
                __('AEOX SEO Analysis', 'aeox-seo'),
                array(AEOX_Meta_Box::get_instance(), 'render'),
                $post_type->name,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box_data($post_id) {
        // Verify nonce
        if (!isset($_POST['aeox_meta_box_nonce']) || !wp_verify_nonce($_POST['aeox_meta_box_nonce'], 'aeox_save_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save SEO data
        if (isset($_POST['aeox_focus_keyword'])) {
            update_post_meta($post_id, '_aeox_focus_keyword', sanitize_text_field($_POST['aeox_focus_keyword']));
        }
        
        if (isset($_POST['aeox_meta_title'])) {
            update_post_meta($post_id, '_aeox_meta_title', sanitize_text_field($_POST['aeox_meta_title']));
        }
        
        if (isset($_POST['aeox_meta_description'])) {
            update_post_meta($post_id, '_aeox_meta_description', sanitize_textarea_field($_POST['aeox_meta_description']));
        }
        
        // Trigger analysis
        AEOX_SEO_Engine::get_instance()->analyze_post($post_id);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on AEOX pages
        if (strpos($hook, 'aeox') === false) {
            return;
        }
        
        wp_enqueue_style('aeox-admin-css', AEOX_PLUGIN_URL . 'assets/css/admin.css', array(), AEOX_VERSION);
        wp_enqueue_script('aeox-admin-js', AEOX_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AEOX_VERSION, true);
        
        wp_localize_script('aeox-admin-js', 'aeoxAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aeox_admin_nonce'),
            'strings' => array(
                'analyzing' => __('Analyzing...', 'aeox-seo'),
                'complete' => __('Analysis Complete', 'aeox-seo'),
                'error' => __('Analysis Error', 'aeox-seo')
            )
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Load only if needed
        if (is_admin()) {
            return;
        }
        
        // Schema output will be handled by the schema engine
    }
    
    /**
     * Add admin bar menu
     */
    public function admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'aeox-seo',
            'title' => '<span class="ab-icon dashicons dashicons-chart-line"></span><span class="ab-label">' . __('AEOX SEO', 'aeox-seo') . '</span>',
            'href' => admin_url('admin.php?page=aeox-seo')
        ));
        
        // Add current page analysis
        if (is_singular()) {
            global $post;
            $wp_admin_bar->add_node(array(
                'id' => 'aeox-analyze',
                'parent' => 'aeox-seo',
                'title' => __('Analyze This Page', 'aeox-seo'),
                'href' => admin_url('post.php?post=' . $post->ID . '&action=edit#aeox-seo-meta-box')
            ));
        }
    }
    
    /**
     * Add action links
     */
    public function action_links($links) {
        $custom_links = array(
            '<a href="' . admin_url('admin.php?page=aeox-settings') . '">' . __('Settings', 'aeox-seo') . '</a>',
            '<a href="' . admin_url('admin.php?page=aeox-seo') . '">' . __('Dashboard', 'aeox-seo') . '</a>'
        );
        
        return array_merge($custom_links, $links);
    }
}

// Initialize the plugin
function aeox_seo_init() {
    return AEOX_SEO::get_instance();
}

// Start the plugin
$GLOBALS['aeox_seo'] = aeox_seo_init();
