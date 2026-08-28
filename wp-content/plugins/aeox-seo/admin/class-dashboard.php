<?php
/**
 * AEOX Admin Dashboard
 *
 * Main admin dashboard for displaying SEO/AEO/GEO scores and analytics.
 *
 * @package AEOX_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AEOX_Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AEOX SEO', 'aeox-seo'),
            __('AEOX SEO', 'aeox-seo'),
            'manage_options',
            'aeox-seo',
            [$this, 'render_dashboard'],
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
            [$this, 'render_dashboard']
        );

        // Settings submenu (placeholder)
        add_submenu_page(
            'aeox-seo',
            __('Settings', 'aeox-seo'),
            __('Settings', 'aeox-seo'),
            'manage_options',
            'aeox-settings',
            [$this, 'render_settings']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_aeox-seo') {
            return;
        }

        wp_enqueue_style('aeox-admin-css', AEOX_PLUGIN_URL . 'assets/css/admin.css', [], AEOX_VERSION);
        wp_enqueue_script('aeox-admin-js', AEOX_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], AEOX_VERSION, true);
        
        wp_localize_script('aeox-admin-js', 'aeoxAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('aeox_admin'),
        ]);
    }

    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        // Get recent analysis data
        $recent_analyses = $this->get_recent_analyses(10);
        $average_scores = $this->get_average_scores();
        
        ?>
        <div class="wrap aeox-dashboard">
            <h1><?php esc_html_e('AEOX SEO Dashboard', 'aeox-seo'); ?></h1>
            
            <div class="aeox-welcome-panel">
                <h2><?php esc_html_e('AI Search Visibility Platform', 'aeox-seo'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Optimize your content for traditional SEO, Answer Engines, and Generative AI search.', 'aeox-seo'); ?>
                </p>
            </div>

            <div class="aeox-stats-grid">
                <!-- Overall Score Card -->
                <div class="aeox-stat-card">
                    <h3><?php esc_html_e('Overall Score', 'aeox-seo'); ?></h3>
                    <div class="aeox-big-score">
                        <span class="aeox-score-value" style="color: <?php echo esc_attr($this->get_score_color($average_scores['overall'])); ?>">
                            <?php echo esc_html($average_scores['overall'] > 0 ? $average_scores['overall'] : '--'); ?>
                        </span>
                    </div>
                    <p class="aeox-score-label"><?php esc_html_e('Across all analyzed content', 'aeox-seo'); ?></p>
                </div>

                <!-- SEO Score Card -->
                <div class="aeox-stat-card aeox-card-seo">
                    <h3><?php esc_html_e('SEO', 'aeox-seo'); ?></h3>
                    <div class="aeox-medium-score">
                        <span class="aeox-score-value" style="color: <?php echo esc_attr($this->get_score_color($average_scores['seo'])); ?>">
                            <?php echo esc_html($average_scores['seo'] > 0 ? $average_scores['seo'] : '--'); ?>
                        </span>
                    </div>
                    <p class="aeox-score-label"><?php esc_html_e('Technical & Content SEO', 'aeox-seo'); ?></p>
                </div>

                <!-- AEO Score Card -->
                <div class="aeox-stat-card aeox-card-aeo">
                    <h3><?php esc_html_e('AEO', 'aeox-seo'); ?></h3>
                    <div class="aeox-medium-score">
                        <span class="aeox-score-value" style="color: <?php echo esc_attr($this->get_score_color($average_scores['aeo'])); ?>">
                            <?php echo esc_html($average_scores['aeo'] > 0 ? $average_scores['aeo'] : '--'); ?>
                        </span>
                    </div>
                    <p class="aeox-score-label"><?php esc_html_e('Answer Engine Optimization', 'aeox-seo'); ?></p>
                </div>

                <!-- GEO Score Card -->
                <div class="aeox-stat-card aeox-card-geo">
                    <h3><?php esc_html_e('GEO', 'aeox-seo'); ?></h3>
                    <div class="aeox-medium-score">
                        <span class="aeox-score-value" style="color: <?php echo esc_attr($this->get_score_color($average_scores['geo'])); ?>">
                            <?php echo esc_html($average_scores['geo'] > 0 ? $average_scores['geo'] : '--'); ?>
                        </span>
                    </div>
                    <p class="aeox-score-label"><?php esc_html_e('Generative Engine Optimization', 'aeox-seo'); ?></p>
                </div>
            </div>

            <div class="aeox-content-grid">
                <!-- Recent Analysis -->
                <div class="aeox-panel">
                    <h2><?php esc_html_e('Recent Analysis', 'aeox-seo'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Post', 'aeox-seo'); ?></th>
                                <th><?php esc_html_e('SEO', 'aeox-seo'); ?></th>
                                <th><?php esc_html_e('AEO', 'aeox-seo'); ?></th>
                                <th><?php esc_html_e('GEO', 'aeox-seo'); ?></th>
                                <th><?php esc_html_e('Date', 'aeox-seo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_analyses)) : ?>
                            <tr>
                                <td colspan="5" class="aeox-no-data">
                                    <?php esc_html_e('No analysis data yet. Start by editing a post.', 'aeox-seo'); ?>
                                </td>
                            </tr>
                            <?php else : ?>
                                <?php foreach ($recent_analyses as $analysis) : 
                                    $post = get_post($analysis->post_id);
                                    if (!$post) continue;
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($analysis->post_id)); ?>">
                                            <?php echo esc_html(get_the_title($analysis->post_id)); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo esc_attr($this->get_score_color($analysis->seo_score)); ?>">
                                            <?php echo esc_html($analysis->seo_score); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo esc_attr($this->get_score_color($analysis->aeo_score)); ?>">
                                            <?php echo esc_html($analysis->aeo_score); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo esc_attr($this->get_score_color($analysis->geo_score)); ?>">
                                            <?php echo esc_html($analysis->geo_score); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($analysis->analyzed_at))); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Quick Actions -->
                <div class="aeox-panel">
                    <h2><?php esc_html_e('Quick Actions', 'aeox-seo'); ?></h2>
                    <ul class="aeox-quick-actions">
                        <li>
                            <a href="<?php echo esc_url(admin_url('edit.php')); ?>" class="button button-primary">
                                <?php esc_html_e('View All Posts', 'aeox-seo'); ?>
                            </a>
                        </li>
                        <li>
                            <span class="aeox-feature-coming-soon">
                                <?php esc_html_e('Run Site Audit (Coming Soon)', 'aeox-seo'); ?>
                            </span>
                        </li>
                        <li>
                            <span class="aeox-feature-coming-soon">
                                <?php esc_html_e('AI Visibility Report (Coming Soon)', 'aeox-seo'); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="aeox-info-panel">
                <h2><?php esc_html_e('About AEOX GEO', 'aeox-seo'); ?></h2>
                <p>
                    <?php esc_html_e('GEO (Generative Engine Optimization) helps your content get cited by AI systems like:', 'aeox-seo'); ?>
                </p>
                <ul class="aeox-ai-list">
                    <li><strong>Google AI Overviews</strong> - <?php esc_html_e('AI-powered search results', 'aeox-seo'); ?></li>
                    <li><strong>Bing Copilot</strong> - <?php esc_html_e('Microsoft AI assistant', 'aeox-seo'); ?></li>
                    <li><strong>ChatGPT</strong> - <?php esc_html_e('OpenAI language model', 'aeox-seo'); ?></li>
                    <li><strong>Perplexity</strong> - <?php esc_html_e('AI search engine with citations', 'aeox-seo'); ?></li>
                </ul>
                <p class="aeox-note">
                    <?php esc_html_e('Our GEO Engine analyzes your content for citation readiness, factual clarity, entity density, answerability, and trust signals.', 'aeox-seo'); ?>
                </p>
            </div>
        </div>

        <style>
            .aeox-dashboard {
                max-width: 1200px;
            }
            .aeox-welcome-panel {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #2271b1;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .aeox-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .aeox-stat-card {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                text-align: center;
            }
            .aeox-stat-card h3 {
                margin: 0 0 15px;
                font-size: 14px;
                color: #646970;
            }
            .aeox-score-value {
                font-size: 48px;
                font-weight: bold;
                color: #2271b1;
            }
            .aeox-score-label {
                margin: 10px 0 0;
                font-size: 12px;
                color: #646970;
            }
            .aeox-content-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
                margin: 20px 0;
            }
            .aeox-panel {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .aeox-panel h2 {
                margin-top: 0;
                font-size: 18px;
                border-bottom: 1px solid #f0f0f1;
                padding-bottom: 10px;
            }
            .aeox-no-data {
                text-align: center;
                color: #646970;
                padding: 40px 20px;
            }
            .aeox-quick-actions {
                list-style: none;
                padding: 0;
            }
            .aeox-quick-actions li {
                margin-bottom: 15px;
            }
            .aeox-feature-coming-soon {
                color: #646970;
                font-style: italic;
            }
            .aeox-info-panel {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .aeox-ai-list {
                list-style: disc;
                margin-left: 20px;
            }
            .aeox-ai-list li {
                margin-bottom: 8px;
            }
            .aeox-note {
                font-size: 13px;
                color: #646970;
                margin-top: 15px;
            }
            @media (max-width: 782px) {
                .aeox-content-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * Get recent analyses from database
     *
     * @param int $limit Number of records to fetch.
     * @return array Recent analysis records.
     */
    private function get_recent_analyses($limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aeox_analysis';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY analyzed_at DESC LIMIT %d",
            $limit
        ));
        
        return $results ? $results : array();
    }
    
    /**
     * Get average scores across all analyzed content
     *
     * @return array Average scores.
     */
    private function get_average_scores() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aeox_analysis';
        
        $row = $wpdb->get_row("
            SELECT 
                AVG(overall_score) as overall,
                AVG(seo_score) as seo,
                AVG(aeo_score) as aeo,
                AVG(geo_score) as geo,
                AVG(schema_score) as schema,
                AVG(entity_score) as entity,
                AVG(content_score) as content
            FROM $table
        ");
        
        if (!$row) {
            return array(
                'overall' => 0,
                'seo' => 0,
                'aeo' => 0,
                'geo' => 0,
                'schema' => 0,
                'entity' => 0,
                'content' => 0,
            );
        }
        
        return array(
            'overall' => round($row->overall),
            'seo' => round($row->seo),
            'aeo' => round($row->aeo),
            'geo' => round($row->geo),
            'schema' => round($row->schema),
            'entity' => round($row->entity),
            'content' => round($row->content),
        );
    }
    
    /**
     * Get color for score based on value
     *
     * @param int $score Score value (0-100).
     * @return string Hex color code.
     */
    private function get_score_color($score) {
        if ($score >= 90) {
            return '#4caf50'; // Green - Excellent
        } elseif ($score >= 80) {
            return '#8bc34a'; // Light Green - Good
        } elseif ($score >= 70) {
            return '#ffeb3b'; // Yellow - Fair
        } elseif ($score >= 60) {
            return '#ff9800'; // Orange - Poor
        } else {
            return '#f44336'; // Red - Critical
        }
    }
    
    /**
     * Render settings page (placeholder)
     */
    public function render_settings() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AEOX SEO Settings', 'aeox-seo'); ?></h1>
            <p><?php esc_html_e('Settings panel coming soon in future versions.', 'aeox-seo'); ?></p>
        </div>
        <?php
    }
}

// Initialize dashboard
if (is_admin()) {
    global $aeox_dashboard;
    $aeox_dashboard = new AEOX_Dashboard();
}
