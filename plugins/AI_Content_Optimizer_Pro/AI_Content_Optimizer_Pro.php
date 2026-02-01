/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content with AI-driven SEO suggestions, readability scores, and meta enhancements.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
    }

    public function activate() {
        add_option('ai_content_optimizer_api_key', '');
        add_option('ai_content_optimizer_enabled', '1');
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
        $enabled = get_option('ai_content_optimizer_enabled', '1');
        $content = get_post_field('post_content', $post->ID);
        if ($enabled && !empty($content)) {
            $score = $this->calculate_readability($content);
            $suggestions = $this->get_ai_suggestions($content);
            echo '<p><strong>Readability Score:</strong> ' . round($score, 2) . '/100</p>';
            echo '<p><strong>AI Suggestions (Free):</strong></p><ul>';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul>';
            echo '<p><em>Upgrade to Pro for full AI optimization, meta tags, and bulk processing!</em></p>';
            echo '<a href="https://example.com/premium" target="_blank" class="button button-primary">Get Pro Version</a>';
        } else {
            echo '<p>Plugin disabled or no content. Enable in settings.</p>';
        }
    }

    private function calculate_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = count($sentences);
        if ($sentences == 0) return 0;
        $syl = $this->count_syllables(strip_tags($content));
        $flesch = 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syl / $words);
        return min(100, max(0, ($flesch / 2.06) * 100)); // Normalized to 0-100
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $syllables = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $syllables += preg_match('/[aeiouy]{2}/', $word) + preg_match('/[aeiouy]$/', $word) ? 1 : 0;
            }
        }
        return max(1, $syllables);
    }

    private function get_ai_suggestions($content) {
        $title = get_the_title(absint(wp_get_post_parent_id(get_the_ID()) ?: get_the_ID()));
        $len = strlen(strip_tags($content));
        $suggestions = array();
        if ($len < 300) $suggestions[] = 'Add more content: Aim for 1000+ words for better SEO.';
        if ($len > 5000) $suggestions[] = 'Shorten content: Keep under 4000 words for readability.';
        $headings = preg_match_all('/<h[1-6]/', $content);
        if ($headings < 3) $suggestions[] = 'Add more headings (H2/H3) for structure.';
        $images = substr_count($content, 'src=');
        if ($images < 2) $suggestions[] = 'Include 2-3 relevant images with alt text.';
        if (empty($suggestions)) $suggestions[] = 'Content looks good! Check keyword density.';
        return $suggestions;
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
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
                settings_fields('ai_content_optimizer');
                do_settings_sections('ai_content_optimizer');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Features:</strong> Unlock AI rewriting, auto-meta generation, and analytics. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ai_content_optimizer', 'ai_content_optimizer_api_key');
        register_setting('ai_content_optimizer', 'ai_content_optimizer_enabled');

        add_settings_section(
            'ai_section',
            'API & General Settings',
            null,
            'ai_content_optimizer'
        );

        add_settings_field(
            'ai_api_key',
            'AI API Key (Pro)',
            array($this, 'api_key_callback'),
            'ai_content_optimizer',
            'ai_section'
        );

        add_settings_field(
            'ai_enabled',
            'Enable Optimizer',
            array($this, 'enabled_callback'),
            'ai_content_optimizer',
            'ai_section'
        );
    }

    public function api_key_callback() {
        $key = get_option('ai_content_optimizer_api_key');
        echo '<input type="text" name="ai_content_optimizer_api_key" value="' . esc_attr($key) . '" class="regular-text" placeholder="Enter your Pro API key" />';
        echo '<p class="description">Get your key from <a href="https://example.com/premium">Pro Dashboard</a>.</p>';
    }

    public function enabled_callback() {
        $enabled = get_option('ai_content_optimizer_enabled', '1');
        echo '<input type="checkbox" name="ai_content_optimizer_enabled" value="1" ' . checked(1, $enabled, false) . ' /> Enable on posts';
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function ai_content_optimizer_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for automated rewriting and SEO boosts! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_content_optimizer_notice');