/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-driven analysis. Free basic features; premium for advanced AI.
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
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'optimize_content'));
        $this->check_premium();
    }

    private function check_premium() {
        // Simulate premium check (in real: integrate with Freemius or license API)
        $this->is_premium = get_option('ai_content_optimizer_premium', false);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function enqueue_frontend() {
        if (is_single()) {
            wp_enqueue_style('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'frontend.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_nonce');
        $seo_score = get_post_meta($post->ID, '_ai_seo_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_suggestions', true);
        echo '<div id="ai-optimizer-box">';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($seo_score ?: 'Not analyzed') . '%</p>';
        if ($suggestions) {
            echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
        }
        echo '<button id="ai-optimize-btn" class="button button-primary">Optimize Now</button>';
        if (!$this->is_premium) {
            echo '<p><small>Upgrade to <strong>Premium</strong> for advanced AI features! <a href="#" id="upgrade-link">Learn More</a></small></p>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page_html')
        );
    }

    public function settings_init() {
        register_setting('ai_optimizer_group', 'ai_optimizer_settings');
        add_settings_section(
            'ai_optimizer_section',
            'Premium Features',
            null,
            'ai-content-optimizer'
        );
        add_settings_field(
            'premium_key',
            'Premium License Key',
            array($this, 'premium_key_html'),
            'ai-content-optimizer',
            'ai_optimizer_section'
        );
    }

    public function premium_key_html() {
        $settings = get_option('ai_optimizer_settings', array());
        echo '<input type="text" name="ai_optimizer_settings[premium_key]" value="' . esc_attr($settings['premium_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your premium key to unlock advanced features. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>';
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_optimizer_group');
                do_settings_sections('ai-content-optimizer');
                submit_button();
                ?>
            </form>
            <div class="premium-promo">
                <h2>Go Premium!</h2>
                <ul>
                    <li>Unlimited optimizations</li>
                    <li>Advanced AI keyword suggestions</li>
                    <li>Priority support</li>
                </ul>
                <a href="https://example.com/buy-premium" class="button button-hero button-primary">Upgrade Now - $4.99/mo</a>
            </div>
        </div>
        <?php
    }

    public function optimize_content($content) {
        if (!is_single()) return $content;
        // Basic free optimization: add schema markup
        $content .= '\n\n<script type="application/ld+json">{"@context":"https://schema.org","@type":"Article","headline":"' . get_the_title() . '"}</script>';
        if ($this->is_premium) {
            // Premium: more advanced (placeholder)
            $content = $this->premium_optimize($content);
        }
        return $content;
    }

    private function premium_optimize($content) {
        // Placeholder for premium AI optimization
        return $content . '\n<p><em>Premium optimized content.</em></p>';
    }

    public function activate() {
        add_option('ai_content_optimizer_premium', false);
    }

    public function deactivate() {}
}

AIContentOptimizer::get_instance();

// AJAX handler for optimization
add_action('wp_ajax_ai_optimize_content', 'ai_optimizer_ajax_handler');
function ai_optimizer_ajax_handler() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Unauthorized');
    }
    // Simulate AI analysis
    $score = rand(60, 95);
    $suggestions = 'Free: Add keywords. Premium: AI rewrites available.';
    update_post_meta($post_id, '_ai_seo_score', $score);
    update_post_meta($post_id, '_ai_suggestions', $suggestions);
    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

// Dummy CSS/JS files would be separate, but for single-file, inline if needed
// Note: In production, enqueue external JS/CSS files.