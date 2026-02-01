/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered analysis for readability, SEO, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results">';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-score"></div>';
        echo '</div>';
        echo '<p><small>Free: Basic score. <a href="#" id="aco-upgrade">Upgrade to Pro</a> for AI rewrite & bulk tools.</small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (basic free version)
        $word_count = str_word_count($content);
        $readability = min(100, 50 + (200 - min($word_count, 200)) * 0.25); // Simple formula
        $seo_score = min(100, rand(40, 80)); // Simulated
        $engagement = min(100, rand(50, 90));
        $overall = round(($readability + $seo_score + $engagement) / 3);

        $results = array(
            'overall' => $overall,
            'readability' => $readability,
            'seo' => $seo_score,
            'engagement' => $engagement,
            'is_premium' => false,
            'message' => $overall > 70 ? 'Good!' : 'Improve readability and SEO.'
        );

        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options') || $this->is_premium()) return;
        echo '<div class="notice notice-info"><p>Unlock AI rewriting and bulk optimization with <strong>AI Content Optimizer Pro</strong> for $9.99/mo. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }

    private function is_premium() {
        return get_option('aco_premium_license') !== false;
    }

    public function activate() {
        update_option('aco_activated', time());
    }
}

AIContentOptimizer::get_instance();

// Inline JS for simplicity (self-contained)
function aco_inline_js() {
    if (!wp_doing_ajax()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function(e) {
                e.preventDefault();
                var post_id = $('#post_ID').val();
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    post_id: post_id
                }, function(response) {
                    if (response.success) {
                        var r = response.data;
                        $('#aco-score').html(
                            '<h4>Score: ' + r.overall + '/100</h4>' +
                            '<p>Readability: ' + r.readability + '</p>' +
                            '<p>SEO: ' + r.seo + '</p>' +
                            '<p>Engagement: ' + r.engagement + '</p>' +
                            '<p>' + r.message + '</p>' +
                            (!r.is_premium ? '<p><em>Pro: AI Rewrite available!</em></p>' : '')
                        );
                    }
                });
            });
            $('#aco-upgrade').click(function(e) {
                e.preventDefault();
                alert('Upgrade to Pro at example.com/pro for advanced features!');
            });
        });
        <?php
    }
}
add_action('admin_footer-post.php', 'aco_inline_js');
add_action('admin_footer-post-new.php', 'aco_inline_js');
?>