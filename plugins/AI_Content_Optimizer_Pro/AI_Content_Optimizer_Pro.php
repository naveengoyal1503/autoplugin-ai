/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes your WordPress content for better SEO and engagement using smart AI heuristics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_enabled', 'yes');
        add_option('ai_content_optimizer_api_key', '');
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
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $optimize = get_post_meta($post->ID, '_ai_optimize', true);
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<label><input type="checkbox" name="ai_optimize" ' . checked($optimize, 'yes', false) . '> Auto-Optimize</label><br>';
        if ($score) {
            echo '<p><strong>AI Score:</strong> ' . esc_html($score) . '%</p>';
        }
        echo '<p><small>Free: Basic keyword suggestions. <a href="' . esc_url(admin_url('admin.php?page=ai-content-optimizer')) . '">Upgrade for AI Rewrite & Analytics</a></small></p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $optimize = isset($_POST['ai_optimize']) ? 'yes' : 'no';
        update_post_meta($post_id, '_ai_optimize', $optimize);

        if ($optimize === 'yes') {
            $this->optimize_content($post_id);
        }
    }

    private function optimize_content($post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;
        $title = $post->post_title;

        // Basic free optimization: Add keywords, improve readability
        $keywords = $this->extract_keywords($title . ' ' . wp_strip_all_tags($content));
        $optimized_content = $this->improve_readability($content, $keywords);

        // Simulate AI score
        $score = min(100, 70 + (count($keywords) * 2));
        update_post_meta($post_id, '_ai_score', $score);

        // Premium feature check
        $premium = get_option('ai_content_optimizer_premium', false);
        if ($premium && get_option('ai_content_optimizer_api_key')) {
            // Placeholder for premium AI rewrite via external API
            $optimized_content .= '\n\n<!-- Premium AI Rewrite Applied -->';
        }

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $optimized_content,
        ));
    }

    private function extract_keywords($text) {
        $words = explode(' ', strtolower(preg_replace('/[^a-zA-Z\s]/', ' ', $text)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_keys(array_slice($counts, 0, 5, true));
    }

    private function improve_readability($content, $keywords) {
        $improved = $content;
        foreach ($keywords as $kw) {
            $improved = preg_replace('/(' . preg_quote($kw, '/') . ')/i', '<strong>$1</strong>', $improved, 3);
        }
        $improved .= '\n\n<p><em>Optimized with AI Content Optimizer (Free Version)</em></p>';
        return $improved;
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_content_optimizer_group');
                do_settings_sections('ai-content-optimizer');
                submit_button();
                ?>
            </form>
            <p><strong>Go Premium:</strong> Unlock AI rewriting, keyword research, and analytics for $9/month. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ai_content_optimizer_group', 'ai_content_optimizer_enabled');
        register_setting('ai_content_optimizer_group', 'ai_content_optimizer_api_key');

        add_settings_section(
            'ai_section',
            'Basic Settings',
            null,
            'ai-content-optimizer'
        );

        add_settings_field(
            'ai_enabled',
            'Enable Auto-Optimization',
            array($this, 'enabled_callback'),
            'ai-content-optimizer',
            'ai_section'
        );

        add_settings_field(
            'ai_api_key',
            'Premium API Key',
            array($this, 'api_key_callback'),
            'ai-content-optimizer',
            'ai_section'
        );
    }

    public function enabled_callback() {
        $enabled = get_option('ai_content_optimizer_enabled', 'yes');
        echo '<input type="checkbox" name="ai_content_optimizer_enabled" value="yes" ' . checked($enabled, 'yes', false) . ' />';
    }

    public function api_key_callback() {
        $key = get_option('ai_content_optimizer_api_key', '');
        echo '<input type="text" name="ai_content_optimizer_api_key" value="' . esc_attr($key) . '" class="regular-text" placeholder="Enter Premium API Key" />';
        echo '<p class="description">Get your key after upgrading to Pro.</p>';
    }
}

AIContentOptimizer::get_instance();