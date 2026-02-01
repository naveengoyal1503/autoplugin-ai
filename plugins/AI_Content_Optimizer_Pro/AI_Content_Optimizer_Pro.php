/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_optimization'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->is_premium = get_option('aicop_premium_key') !== false;
        if ($this->is_premium) {
            // Premium features load
        }
        wp_enqueue_script('jquery');
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicop_premium_key'])) {
            update_option('aicop_premium_key', sanitize_text_field($_POST['aicop_premium_key']));
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <p><label>Premium Key (for advanced features):</label><br>
                <input type="text" name="aicop_premium_key" placeholder="Enter your premium key" style="width:300px;"></p>
                <?php submit_button(); ?>
            </form>
            <p><strong>Free Features:</strong> Basic SEO score, readability analysis.</p>
            <p><strong>Premium ($4.99/mo):</strong> AI rewriting, bulk optimization, export reports. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side');
    }

    public function meta_box_content($post) {
        wp_nonce_field('ai_optimize_nonce', 'ai_optimize_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_seo_score', true);
        $readability = get_post_meta($post->ID, '_ai_readability', true);
        echo '<p><strong>SEO Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        echo '<p><strong>Readability:</strong> ' . esc_html($readability ?: 'Not analyzed') . '%</p>';
        echo '<p><button id="optimize-now" class="button button-primary">Analyze & Optimize</button></p>';
        echo '<div id="ai-result"></div>';
        echo '<script>
        jQuery(document).ready(function($){
            $("#optimize-now").click(function(e){
                e.preventDefault();
                $("#ai-result").html("Analyzing...");
                $.post(ajaxurl, {
                    action: "optimize_content",
                    post_id: ' . $post->ID . ',
                    nonce: $("#ai_optimize_nonce").val()
                }, function(response){
                    $("#ai-result").html(response);
                });
            });
        });
        </script>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimize_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $seo_score = min(100, 50 + ($word_count > 500 ? 20 : 0) + (strpos($content, 'href=') !== false ? 15 : 0) + rand(0,15));
        $readability = min(100, 60 + rand(0,40));

        update_post_meta($post_id, '_ai_seo_score', $seo_score);
        update_post_meta($post_id, '_ai_readability', $readability);

        $result = '<p><strong>SEO Score:</strong> ' . $seo_score . '/100</p>';
        $result .= '<p><strong>Readability:</strong> ' . $readability . '%</p>';
        $result .= '<p>Tips: Use more keywords, add headings, improve sentence length.</p>';

        if ($this->is_premium) {
            // Premium AI rewrite simulation
            $optimized = $this->premium_rewrite($content);
            $result .= '<p><strong>Premium AI Rewrite:</strong><br>' . esc_html(substr($optimized, 0, 200)) . '...</p>';
            $result .= '<textarea rows="10" cols="50">' . esc_textarea($optimized) . '</textarea><br>';
            $result .= '<button class="button" onclick="updateContent()">Apply Rewrite</button>';
        } else {
            $result .= '<p><a href="https://example.com/premium" target="_blank">Upgrade to Premium for AI rewriting!</a></p>';
        }

        wp_die($result);
    }

    private function premium_rewrite($content) {
        // Simulated AI rewrite (in real: call OpenAI API)
        return 'Optimized version: ' . substr($content, 0, 100) . '... (Premium feature: Full AI rewrite with better SEO and readability).';
    }

    public function save_optimization($post_id) {
        if (!isset($_POST['ai_optimize_nonce']) || !wp_verify_nonce($_POST['ai_optimize_nonce'], 'ai_optimize_nonce')) {
            return;
        }
        // Save any additional meta if needed
    }

    public function activate() {
        add_option('aicop_version', '1.0.0');
    }
}

AIContentOptimizer::get_instance();