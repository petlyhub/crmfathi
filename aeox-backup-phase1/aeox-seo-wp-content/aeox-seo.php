<?php
/**
 * Plugin Name: AEOX SEO
 * Plugin URI: https://example.com/aeox-seo
 * Description: AI Search Optimization Platform for WordPress - SEO + AEO + GEO + Entity Optimization
 * Version: 1.0.0
 * Author: AEOX Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aeox-seo
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AEOX_VERSION', '1.0.0');
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
        $this->init_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Initialize constants
     */
    private function init_constants() {
        // Already defined above, but can add more here
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core files
        require_once AEOX_PLUGIN_DIR . 'core/class-database.php';
        require_once AEOX_PLUGIN_DIR . 'core/class-cache.php';
        
        // SEO Engine
        require_once AEOX_PLUGIN_DIR . 'seo/engine.php';
        
        // AEO Engine
        require_once AEOX_PLUGIN_DIR . 'aeo/engine.php';
        
        // GEO Engine Module (includes engine.php internally)
        require_once AEOX_PLUGIN_DIR . 'geo/module.php';
        
        // Schema Engine
        // require_once AEOX_PLUGIN_DIR . 'schema/module.php';
        
        // Entity Engine
        // require_once AEOX_PLUGIN_DIR . 'entity/module.php';
        
        // Admin
        if (is_admin()) {
            require_once AEOX_PLUGIN_DIR . 'admin/class-dashboard.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init_database']);
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('aeox-seo', false, dirname(AEOX_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Initialize database tables
     */
    public function init_database() {
        global $aeox_db;
        require_once AEOX_PLUGIN_DIR . 'core/class-database.php';
        $aeox_db = new AEOX_Database();
        $aeox_db->create_tables();
    }

    /**
     * Activation hook
     */
    public function activate() {
        // Create database tables
        $this->init_database();
        
        // Set default options
        add_option('aeox_version', AEOX_VERSION);
        add_option('aeox_settings', [
            'enable_seo' => true,
            'enable_aeo' => true,
            'enable_geo' => true,
            'enable_schema' => true,
            'enable_entity' => true,
        ]);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function aeox_seo_init() {
    return AEOX_SEO::get_instance();
}

// Start the plugin
aeox_seo_init();
