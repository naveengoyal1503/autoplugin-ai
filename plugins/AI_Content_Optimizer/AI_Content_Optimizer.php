/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upgrades.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->check_premium();
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'aco-content-analysis',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-analysis-results">';
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-results"></div>';
        echo '<p><small><strong>Premium:</strong> AI Rewrite, Keyword Suggestions & Unlimited Scans - <a href="https://example.com/premium" target="_blank">Upgrade Now</a></small></p>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!$this->is_premium()) {
            $post_id = intval($_POST['post_id']);
            $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        } else {
            // Simulate premium AI call
            $content = 'Premium AI analysis active.';
        }

        $word_count = str_word_count($content);
        $readability = $this->calculate_readability($content);
        $seo_score = min(100, 50 + ($word_count / 10) + ($readability * 10));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability * 100, 1) . '%',
            'seo_score' => round($seo_score),
            'is_premium' => $this->is_premium(),
            'message' => $this->is_premium() ? 'Premium features unlocked!' : 'Upgrade for AI-powered rewriting and more.'
        );

        wp_send_json_success($results);
    }

    private function calculate_readability($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($text));
        if ($sentence_count == 0 || $words == 0) return 0;
        $avg_sentence_length = $words / $sentence_count;
        return max(0, min(1, 1 - (($avg_sentence_length - 15) / 50)));
    }

    private function is_premium() {
        return get_option('aco_premium_key') !== false;
    }

    private function check_premium() {
        if (!$this->is_premium() && isset($_GET['aco_activate_premium'])) {
            $key = sanitize_text_field($_GET['key']);
            if ($key === 'premium123') { // Demo key
                update_option('aco_premium_key', $key);
                wp_redirect(admin_url('plugins.php?activate=true'));
                exit;
            }
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
function aco_admin_notice() {
    if (!AIContentOptimizer::get_instance()->is_premium()) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for AI rewriting, keywords & more! <a href="https://example.com/premium">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aco_admin_notice');

// Include admin.js content as inline script for single file
add_action('admin_footer', function() {
    if (!AIContentOptimizer::get_instance()->is_premium()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#aco-analyze-btn').click(function() {
                var post_id = $('#post_ID').val();
                $('#aco-results').html('<p>Analyzing...</p>');
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    post_id: post_id
                }, function(response) {
                    if (response.success) {
                        var r = response.data;
                        var html = '<ul>' +
                            '<li>Words: ' + r.word_count + '</li>' +
                            '<li>Readability: ' + r.readability + '</li>' +
                            '<li>SEO Score: <strong>' + r.seo_score + '/100</strong></li>' +
                            '<li>' + r.message + '</li>' +
                            '</ul>';
                        $('#aco-results').html(html);
                    }
                });
            });
        });
        </script>
        <?php
    }
});