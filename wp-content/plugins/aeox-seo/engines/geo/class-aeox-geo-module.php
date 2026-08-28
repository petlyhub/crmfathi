<?php
/**
 * AEOX GEO Module Integration
 *
 * Integrates the GEO Engine with WordPress hooks and filters.
 *
 * @package AEOX_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AEOX_GEO_Module {

    /**
     * Instance of the GEO Engine
     */
    private $engine;

    /**
     * Constructor
     */
    public function __construct() {
        require_once AEOX_PLUGIN_DIR . 'engines/geo/class-aeox-geo-engine.php';
        $this->engine = new AEOX_GEO_Engine();
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Save GEO analysis on post save
        add_action('save_post', [$this, 'analyze_on_save'], 20, 2);
        
        // Add GEO meta box to post editor
        add_action('add_meta_boxes', [$this, 'add_geo_metabox']);
        
        // AJAX handler for manual re-analysis
        add_action('wp_ajax_aeox_geo_analyze', [$this, 'ajax_analyze']);
        
        // Add GEO score to admin columns
        add_filter('manage_posts_columns', [$this, 'add_geo_column']);
        add_action('manage_posts_custom_column', [$this, 'render_geo_column'], 10, 2);
    }

    /**
     * Analyze content when post is saved
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function analyze_on_save($post_id, $post) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Only analyze published posts or drafts with content
        if (!in_array($post->post_status, ['publish', 'draft', 'future'])) {
            return;
        }

        // Perform analysis
        $analysis = $this->engine->analyze($post_id);

        if (isset($analysis['error'])) {
            return;
        }

        // Save scores to post meta
        update_post_meta($post_id, '_aeox_geo_scores', $analysis['scores']);
        update_post_meta($post_id, '_aeox_geo_issues', $analysis['issues']);
        update_post_meta($post_id, '_aeox_geo_opportunities', $analysis['opportunities']);
        update_post_meta($post_id, '_aeox_geo_data', $analysis['data']);
        update_post_meta($post_id, '_aeox_geo_last_analyzed', current_time('timestamp'));
    }

    /**
     * Add GEO Analysis Meta Box
     */
    public function add_geo_metabox() {
        $screens = ['post', 'page'];
        
        foreach ($screens as $screen) {
            add_meta_box(
                'aeox_geo_metabox',
                __('AEOX GEO Analysis', 'aeox-seo'),
                [$this, 'render_metabox'],
                $screen,
                'side',
                'high'
            );
        }
    }

    /**
     * Render GEO Meta Box content
     *
     * @param WP_Post $post Current post object.
     */
    public function render_metabox($post) {
        wp_nonce_field('aeox_geo_meta_box', 'aeox_geo_meta_box_nonce');

        $scores = get_post_meta($post->ID, '_aeox_geo_scores', true);
        $last_analyzed = get_post_meta($post->ID, '_aeox_geo_last_analyzed', true);

        // If no analysis exists yet
        if (empty($scores)) {
            echo '<p>' . esc_html__('No GEO analysis available yet. Publish or update the post to generate analysis.', 'aeox-seo') . '</p>';
            echo '<button type="button" class="button button-primary" id="aeox-geo-analyze-btn">' . esc_html__('Analyze Now', 'aeox-seo') . '</button>';
            return;
        }

        $total_score = isset($scores['total_geo_score']) ? $scores['total_geo_score'] : 0;
        
        // Determine color based on score
        $color_class = 'aeox-score-low';
        if ($total_score >= 80) {
            $color_class = 'aeox-score-good';
        } elseif ($total_score >= 60) {
            $color_class = 'aeox-score-medium';
        }

        ?>
        <div class="aeox-geo-dashboard">
            <div class="aeox-score-circle <?php echo esc_attr($color_class); ?>">
                <svg viewBox="0 0 36 36" class="aeox-circular-chart">
                    <path class="aeox-circle-bg"
                        d="M18 2.0845
                          a 15.9155 15.9155 0 0 1 0 31.831
                          a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="#eee"
                        stroke-width="3"
                    />
                    <path class="aeox-circle"
                        stroke-dasharray="<?php echo esc_attr($total_score); ?>, 100"
                        d="M18 2.0845
                          a 15.9155 15.9155 0 0 1 0 31.831
                          a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                    />
                    <text x="18" y="20.35" class="aeox-circle-number" text-anchor="middle" font-size="10">
                        <?php echo esc_html($total_score); ?>
                    </text>
                </svg>
            </div>
            
            <h4><?php esc_html_e('GEO Score', 'aeox-seo'); ?></h4>
            <p class="description"><?php esc_html_e('Generative Engine Optimization', 'aeox-seo'); ?></p>

            <div class="aeox-geo-breakdown">
                <div class="aeox-geo-item">
                    <span class="aeox-label"><?php esc_html_e('Citation', 'aeox-seo'); ?></span>
                    <span class="aeox-value"><?php echo esc_html($scores['citation_readiness'] ?? 0); ?></span>
                </div>
                <div class="aeox-geo-item">
                    <span class="aeox-label"><?php esc_html_e('Factual', 'aeox-seo'); ?></span>
                    <span class="aeox-value"><?php echo esc_html($scores['factual_clarity'] ?? 0); ?></span>
                </div>
                <div class="aeox-geo-item">
                    <span class="aeox-label"><?php esc_html_e('Entity', 'aeox-seo'); ?></span>
                    <span class="aeox-value"><?php echo esc_html($scores['entity_density'] ?? 0); ?></span>
                </div>
                <div class="aeox-geo-item">
                    <span class="aeox-label"><?php esc_html_e('Answer', 'aeox-seo'); ?></span>
                    <span class="aeox-value"><?php echo esc_html($scores['answerability'] ?? 0); ?></span>
                </div>
                <div class="aeox-geo-item">
                    <span class="aeox-label"><?php esc_html_e('Trust', 'aeox-seo'); ?></span>
                    <span class="aeox-value"><?php echo esc_html($scores['trust_signals'] ?? 0); ?></span>
                </div>
            </div>

            <?php if ($last_analyzed): ?>
                <p class="aeox-last-updated">
                    <small>
                        <?php 
                        printf(
                            esc_html__('Last analyzed: %s ago', 'aeox-seo'),
                            human_time_diff($last_analyzed, current_time('timestamp'))
                        );
                        ?>
                    </small>
                </p>
            <?php endif; ?>

            <button type="button" class="button button-secondary" id="aeox-geo-reanalyze-btn" style="width:100%">
                <?php esc_html_e('Re-Analyze', 'aeox-seo'); ?>
            </button>
        </div>

        <style>
            .aeox-geo-dashboard {
                text-align: center;
                padding: 10px 0;
            }
            .aeox-score-circle {
                width: 100px;
                height: 100px;
                margin: 0 auto 15px;
                position: relative;
            }
            .aeox-circular-chart {
                width: 100%;
                height: 100%;
                transform: rotate(-90deg);
            }
            .aeox-circle-bg {
                stroke: #f0f0f1;
            }
            .aeox-circle {
                transition: stroke-dasharray 0.5s ease;
            }
            .aeox-score-good .aeox-circle {
                color: #00a32a;
            }
            .aeox-score-medium .aeox-circle {
                color: #dba617;
            }
            .aeox-score-low .aeox-circle {
                color: #d63638;
            }
            .aeox-circle-number {
                transform: rotate(90deg);
                transform-origin: center;
                font-weight: bold;
                fill: #3c434a;
            }
            .aeox-geo-breakdown {
                margin: 20px 0;
                text-align: left;
            }
            .aeox-geo-item {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            .aeox-label {
                font-weight: 500;
                color: #3c434a;
            }
            .aeox-value {
                font-weight: bold;
                color: #2271b1;
            }
            .aeox-last-updated {
                margin-top: 15px;
                color: #646970;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#aeox-geo-reanalyze-btn').on('click', function() {
                var postId = <?php echo esc_js($post->ID); ?>;
                var $btn = $(this);
                
                $btn.prop('disabled', true).text('<?php esc_html_e('Analyzing...', 'aeox-seo'); ?>');
                
                $.post(ajaxurl, {
                    action: 'aeox_geo_analyze',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('aeox_geo_analyze'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php esc_html_e('Analysis failed. Please try again.', 'aeox-seo'); ?>');
                        $btn.prop('disabled', false).text('<?php esc_html_e('Re-Analyze', 'aeox-seo'); ?>');
                    }
                });
            });

            $('#aeox-geo-analyze-btn').on('click', function() {
                var postId = <?php echo esc_js($post->ID); ?>;
                var $btn = $(this);
                
                // First save the post
                $('#publish').click();
                
                setTimeout(function() {
                    $btn.prop('disabled', true).text('<?php esc_html_e('Analyzing...', 'aeox-seo'); ?>');
                    
                    $.post(ajaxurl, {
                        action: 'aeox_geo_analyze',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('aeox_geo_analyze'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }, 1000);
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for GEO analysis
     */
    public function ajax_analyze() {
        // Verify nonce
        check_ajax_referer('aeox_geo_analyze', 'nonce');

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        // Run analysis
        $analysis = $this->engine->analyze($post_id);

        if (isset($analysis['error'])) {
            wp_send_json_error(['message' => $analysis['error']]);
        }

        // Save results
        update_post_meta($post_id, '_aeox_geo_scores', $analysis['scores']);
        update_post_meta($post_id, '_aeox_geo_issues', $analysis['issues']);
        update_post_meta($post_id, '_aeox_geo_opportunities', $analysis['opportunities']);
        update_post_meta($post_id, '_aeox_geo_data', $analysis['data']);
        update_post_meta($post_id, '_aeox_geo_last_analyzed', current_time('timestamp'));

        wp_send_json_success([
            'scores' => $analysis['scores'],
            'post_id' => $post_id
        ]);
    }

    /**
     * Add GEO Score column to posts list
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_geo_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['aeox_geo_score'] = __('GEO Score', 'aeox-seo');
            }
        }
        
        return $new_columns;
    }

    /**
     * Render GEO Score column content
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_geo_column($column, $post_id) {
        if ($column !== 'aeox_geo_score') {
            return;
        }

        $scores = get_post_meta($post_id, '_aeox_geo_scores', true);
        
        if (empty($scores) || !isset($scores['total_geo_score'])) {
            echo '<span class="aeox-no-score">—</span>';
            return;
        }

        $score = $scores['total_geo_score'];
        
        $class = 'aeox-score-badge ';
        if ($score >= 80) {
            $class .= 'aeox-good';
        } elseif ($score >= 60) {
            $class .= 'aeox-medium';
        } else {
            $class .= 'aeox-low';
        }

        echo '<span class="' . esc_attr($class) . '">' . esc_html($score) . '</span>';
    }
}

// Initialize the module
global $aeox_geo_module;
$aeox_geo_module = new AEOX_GEO_Module();
