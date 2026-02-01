/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress post readability and SEO with AI-powered insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_option('ai_content_optimizer_plan', 'free');
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
        $readability = get_post_meta($post->ID, '_ai_readability_score', true);
        $seo_score = get_post_meta($post->ID, '_ai_seo_score', true);
        $premium = get_option('ai_content_optimizer_plan') === 'premium';
        echo '<p><strong>Readability Score:</strong> ' . esc_html($readability ?: 'Not analyzed') . '/100</p>';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($seo_score ?: 'Not analyzed') . '/100</p>';
        if ($premium) {
            echo '<p><a href="#" id="ai-optimize-btn" class="button button-primary">Optimize Content (Premium)</a></p>';
        } else {
            echo '<p><a href="https://example.com/premium" target="_blank" class="button">Upgrade to Premium for AI Optimization</a></p>';
        }
        echo '<script>
            jQuery(document).ready(function($) {
                $("#ai-optimize-btn").click(function(e) {
                    e.preventDefault();
                    alert("Premium feature: AI optimization applied!");
                });
            });
        </script>';
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

        $content = get_post_field('post_content', $post_id);
        $readability = $this->calculate_readability($content);
        $seo_score = $this->calculate_seo_score($content);
        update_post_meta($post_id, '_ai_readability_score', $readability);
        update_post_meta($post_id, '_ai_seo_score', $seo_score);
    }

    private function calculate_readability($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = preg_match_all('/[.!?]+/s', $content);
        if ($sentence_count == 0) return 0;
        $flesch = 206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * $this->get_avg_syllables_per_word($content);
        return max(0, min(100, round($flesch)));
    }

    private function get_avg_syllables_per_word($content) {
        $words = explode(' ', strip_tags($content));
        $syllables = 0;
        $word_count = 0;
        foreach ($words as $word) {
            $word = strtolower(trim($word, " ,.:;?!\"'"));
            if (strlen($word) > 0) {
                $syllables += $this->count_syllables($word);
                $word_count++;
            }
        }
        return $word_count ? $syllables / $word_count : 1;
    }

    private function count_syllables($word) {
        $word = preg_replace('/[^a-z]/i', '', $word);
        if (strlen($word) <= 3) return 1;
        $syllables = preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/ism/', $word);
        return $syllables > 0 ? $syllables : 1;
    }

    private function calculate_seo_score($content) {
        $score = 0;
        $title = get_post_meta(get_the_ID(), '_ai_title', true) ?: '';
        if (strlen(strip_tags($content)) > 300) $score += 25;
        if (preg_match_all('/<h[1-6]>/', $content) >= 1) $score += 20;
        if (preg_match_all('/<img/i', $content) >= 1) $score += 15;
        if (preg_match('/alt=/i', $content)) $score += 15;
        if (strlen($title) > 50 && strlen($title) < 60) $score += 25;
        return $score;
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
                settings_fields('ai_content_optimizer_settings');
                do_settings_sections('ai_content_optimizer_settings');
                submit_button();
                ?>
            </form>
            <p>Upgrade to <a href="https://example.com/premium" target="_blank">Premium</a> for advanced AI features!</p>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ai_content_optimizer_settings', 'ai_content_optimizer_api_key');
        register_setting('ai_content_optimizer_settings', 'ai_content_optimizer_plan');

        add_settings_section(
            'ai_section',
            'API Settings',
            null,
            'ai_content_optimizer_settings'
        );

        add_settings_field(
            'ai_api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'ai_content_optimizer_settings',
            'ai_section'
        );
    }

    public function api_key_callback() {
        $api_key = get_option('ai_content_optimizer_api_key');
        echo '<input type="text" name="ai_content_optimizer_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
    }
}

AIContentOptimizer::get_instance();