/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_URL = 'https://example.com/upgrade'; // Replace with your premium site

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_aicop_analyze', array($this, 'ajax_analyze'));
        add_action('wp_ajax_aicop_upgrade', array($this, 'ajax_upgrade'));
    }

    public function activate() {
        add_option('aicop_activated', time());
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_meta_box() {
        add_meta_box('aicop-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aicop-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aicop_meta_box', 'aicop_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_aicop_score', true);
        $is_premium = $this->is_premium();
        echo '<div id="aicop-results">';
        if ($score) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '%</p>';
            echo '<p><strong>SEO Score:</strong> ' . esc_html($this->calculate_seo_score($content)) . '%</p>';
        }
        echo '</div>';
        echo '<p><button id="aicop-analyze" class="button button-secondary">Analyze Content</button></p>';
        if (!$is_premium) {
            echo '<p><em>Upgrade to Pro for AI rewriting and bulk tools!</em> <a href="#" id="aicop-upgrade">Upgrade Now</a></p>';
        }
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['aicop_meta_box_nonce']) || !wp_verify_nonce($_POST['aicop_meta_box_nonce'], 'aicop_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        // Score saved via AJAX
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicop-admin', 'aicop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_nonce'),
            'is_premium' => $this->is_premium(),
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'aicop-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicop_pro_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aicop_pro_key']));
            echo '<div class="notice notice-success"><p>Key updated!</p></div>';
        }
        $key = get_option(self::PREMIUM_KEY, '');
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post"><table class="form-table">';
        echo '<tr><th>Premium License Key</th><td><input type="text" name="aicop_pro_key" value="' . esc_attr($key) . '" class="regular-text" /> <p class="description">Enter your Pro key for premium features.</p></td></tr>';
        echo '</table><p><input type="submit" class="button-primary" value="Save Key" /></p></form>';
        echo '<p><a href="' . self::PREMIUM_URL . '" class="button button-primary" target="_blank">Upgrade to Pro ($4.99/mo)</a></p>';
        echo '</div>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        $readability = $this->calculate_readability($content);
        $seo = $this->calculate_seo_score($content);
        $score = ($readability + $seo) / 2;

        update_post_meta($post_id, '_aicop_score', $score);

        wp_send_json_success(array('score' => round($score, 1), 'readability' => $readability, 'seo' => $seo));
    }

    public function ajax_upgrade() {
        check_ajax_referer('aicop_nonce', 'nonce');
        wp_send_json(array('url' => self::PREMIUM_URL));
    }

    private function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return !empty($key) && strlen($key) > 10; // Simple check; validate remotely in pro
    }

    private function calculate_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        $syllables = $this->count_syllables(strip_tags($content));
        if ($sentences == 0) return 50;
        $asl = $words / $sentences;
        $asw = $syllables / $words;
        $flesch = 206.835 - (1.015 * $asl) - (84.6 * $asw);
        return max(0, min(100, round(206.835 - (1.015 * $asl) - (84.6 * $asw))));
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]+/', $text, $matches);
        return count($matches);
    }

    private function calculate_seo_score($content) {
        $score = 50;
        $title = get_the_title(get_the_ID()); // Approximate
        if (stripos($content, $title) !== false) $score += 20;
        $word_count = str_word_count(strip_tags($content));
        if ($word_count > 300 && $word_count < 2000) $score += 15;
        $h_tags = preg_match_all('/<h[1-6][^>]*>/i', $content);
        if ($h_tags > 1) $score += 10;
        $images = preg_match_all('/<img[^>]+>/i', $content);
        if ($images > 0) $score += 5;
        return min(100, $score);
    }
}

new AIContentOptimizer();

// Freemium notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options') || AIContentOptimizer::is_premium()) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting, bulk optimization, and more! <a href="' . admin_url('options-general.php?page=aicop-settings') . '">Upgrade Now</a></p></div>;
});

// Dummy admin.js content (in real, enqueue separate file)
/*
$(document).ready(function($) {
    $('#aicop-analyze').click(function(e) {
        e.preventDefault();
        $.post(aicop_ajax.ajax_url, {
            action: 'aicop_analyze',
            post_id: $('#post_ID').val(),
            nonce: aicop_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#aicop-results').html('<p><strong>Readability: ' + res.data.readability + '%</strong></p><p><strong>SEO: ' + res.data.seo + '%</strong></p><p><strong>Overall: ' + res.data.score + '%</strong></p>');
            }
        });
    });
    $('#aicop-upgrade').click(function(e) {
        e.preventDefault();
        window.open(aicop_ajax.premium_url, '_blank');
    });
});
*/
?>