/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-driven analysis.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_ai_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        // Freemium check
        $this->check_premium_status();
    }

    private function check_premium_status() {
        // Simulate license check; in real, use API or option
        $premium = get_option('ai_content_optimizer_premium', false);
        if (!$premium) {
            add_action('admin_notices', array($this, 'premium_notice'));
        }
    }

    public function premium_notice() {
        echo '<div class="notice notice-info"><p>' . __('Upgrade to <strong>AI Content Optimizer Pro</strong> for unlimited optimizations and advanced features!', 'ai-content-optimizer') . '</p></div>';
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_license_key'])) {
            update_option('ai_content_optimizer_premium', true);
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <p>
                    <label for="ai_license_key"><?php _e('Enter Premium License Key:', 'ai-content-optimizer'); ?></label><br>
                    <input type="text" name="ai_license_key" id="ai_license_key" class="regular-text">
                    <input type="submit" class="button-primary" value="<?php _e('Activate Premium', 'ai-content-optimizer'); ?>">
                </p>
            </form>
            <p><?php _e('Free version: 5 scans/day. Premium: Unlimited + AI auto-fixes.', 'ai-content-optimizer'); ?></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimize_nonce', 'ai_optimize_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimize_score', true);
        $limit = $this->get_scan_limit();
        if ($limit <= 0) {
            echo '<p>' . __('Daily limit reached. Upgrade to premium!', 'ai-content-optimizer') . '</p>';
            return;
        }
        echo '<p><strong>SEO Score:</strong> ' . ($score ?: 'Not scanned') . '%</p>';
        echo '<p><button id="ai-scan-content" class="button">Scan & Optimize</button></p>';
        echo '<div id="ai-results"></div>';
        echo '<script>
            jQuery(document).ready(function($) {
                $("#ai-scan-content").click(function(e) {
                    e.preventDefault();
                    $("#ai-results").html("Scanning...");
                    $.post(ajaxurl, {
                        action: "ai_optimize_content",
                        post_id: ' . $post->ID . ',
                        nonce: $("#ai_optimize_nonce").val()
                    }, function(response) {
                        $("#ai-results").html(response);
                    });
                });
            });
        </script>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimize_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $limit = $this->get_scan_limit();
        if ($limit <= 0) {
            wp_die(__('Limit reached.', 'ai-content-optimizer'));
        }
        $this->decrement_scan_limit();

        // Simulate AI analysis (in real: API call to OpenAI or similar)
        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count / 10) + rand(0, 20)); // Mock score
        $suggestions = $this->generate_suggestions($content);

        update_post_meta($post_id, '_ai_optimize_score', $score);

        ob_start();
        echo '<p><strong>Score: ' . $score . '%</strong></p>';
        echo '<ul>';
        foreach ($suggestions as $sugg) {
            echo '<li>' . $sugg . '</li>';
        }
        echo '</ul>';
        if (get_option('ai_content_optimizer_premium', false)) {
            echo '<p><button id="ai-auto-fix" class="button button-primary">Auto-Apply Fixes (Premium)</button></p>';
        }
        $output = ob_get_clean();
        wp_die($output);
    }

    private function generate_suggestions($content) {
        $suggestions = array(
            'Add more keywords like "WordPress" and "SEO".',
            'Improve readability: Aim for shorter sentences.',
            'Add headings (H2/H3) for better structure.',
            'Include a call-to-action at the end.',
            'Optimize images with alt text.'
        );
        return $suggestions;
    }

    private function get_scan_limit() {
        $today = date('Y-m-d');
        $used = get_option('ai_scans_used_' . $today, 0);
        return 5 - $used; // Free limit: 5/day
    }

    private function decrement_scan_limit() {
        $today = date('Y-m-d');
        $used = get_option('ai_scans_used_' . $today, 0) + 1;
        update_option('ai_scans_used_' . $today, $used);
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimize_nonce']) || !wp_verify_nonce($_POST['ai_optimize_nonce'], 'ai_optimize_nonce')) {
            return;
        }
        // Handle auto-fix if premium
        if (isset($_POST['ai_auto_fix']) && get_option('ai_content_optimizer_premium', false)) {
            // Simulate auto-fix
            $content = get_post_field('post_content', $post_id);
            $content .= '\n\n<h2>Optimized with AI!</h2>';
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));
        }
    }
}

AIContentOptimizer::get_instance();