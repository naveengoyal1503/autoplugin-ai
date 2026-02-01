/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
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
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Freemium check
        add_action('admin_notices', array($this, 'premium_notice'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = $this->calculate_readability($content);
        $keywords = $this->extract_keywords($content);
        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>Readability Score:</strong> ' . round($score, 2) . '/100</p>';
        echo '<p><strong>Keywords:</strong> ' . implode(', ', array_slice($keywords, 0, 5)) . '</p>';
        echo '<p id="premium-teaser">Unlock AI optimizations and auto-fixes with Premium! <a href="#" id="upgrade-btn">Upgrade Now</a></p>';
        echo '</div>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    private function calculate_readability($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = preg_match_all('/[.!?]+/', $content);
        $syllables = $this->count_syllables($content);
        if ($sentence_count == 0) return 0;
        $words_per_sentence = $word_count / $sentence_count;
        $flesch = 206.835 - 1.015 * $words_per_sentence - 84.6 * ($syllables / $word_count);
        return max(0, min(100, $flesch));
    }

    private function count_syllables($text) {
        $text = strtolower(strip_tags($text));
        $text = preg_replace('/[^a-z\s]/', '', $text);
        preg_match_all('/[a-z]+/', $text, $matches);
        $syllables = 0;
        foreach ($matches as $word) {
            $syllables += substr_count(preg_replace('/[^aeiouy]/', '', $word), 'a') +
                          substr_count(preg_replace('/[^aeiouy]/', '', $word), 'e') +
                          substr_count(preg_replace('/[^aeiouy]/', '', $word), 'i') +
                          substr_count(preg_replace('/[^aeiouy]/', '', $word), 'o') +
                          substr_count(preg_replace('/[^aeiouy]/', '', $word), 'u') +
                          substr_count(preg_replace('/[^aeiouy]/', '', $word), 'y');
        }
        return $syllables;
    }

    private function extract_keywords($content) {
        $words = explode(' ', strip_tags(strtolower($content)));
        $word_freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($word_freq);
        return array_keys(array_slice($word_freq, 0, 10));
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page_callback')
        );
    }

    public function settings_page_callback() {
        if (isset($_POST['ai_license_key'])) {
            update_option('ai_optimizer_license', sanitize_text_field($_POST['ai_license_key']));
            echo '<div class="notice notice-success"><p>License updated!</p></div>';
        }
        $license = get_option('ai_optimizer_license', '');
        echo '<div class="wrap">';
        echo '<h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post">';
        echo '<p><label>Premium License Key: <input type="text" name="ai_license_key" value="' . esc_attr($license) . '" /></label></p>';
        submit_button();
        echo '</form>';
        echo '<p><strong>Free Features:</strong> Readability score, keyword extraction.</p>';
        echo '<p><strong>Premium ($49/year):</strong> AI rewrite suggestions, SEO meta optimizer, auto-publish. <a href="https://example.com/premium" target="_blank">Buy Now</a></p>';
        echo '</div>';
    }

    public function premium_notice() {
        if (get_option('ai_optimizer_license') === 'valid') return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features for $49/year: AI optimizations, advanced SEO tools. <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Activate Premium</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

AIContentOptimizer::get_instance();

// Dummy JS/CSS placeholders (in real plugin, create separate files)
function ai_optimizer_ajax() {
    wp_register_script('optimizer-js', '', array(), '', true);
    wp_add_inline_script('optimizer-js', 'jQuery(document).ready(function($){ $("#upgrade-btn").click(function(e){ e.preventDefault(); alert("Upgrade to Pro for AI features!"); }); });');
    wp_register_style('optimizer-css', '');
    wp_add_inline_style('optimizer-css', '#ai-optimizer-results { padding: 10px; background: #f9f9f9; border: 1px solid #ddd; } #premium-teaser { color: #0073aa; font-weight: bold; }');
}
add_action('admin_enqueue_scripts', 'ai_optimizer_ajax', 99);